import { useEffect, useRef, useState } from 'react';
import JsBarcode from 'jsbarcode';
import { apiRequest, jsonBody } from '../api';
import { useApp } from '../context/AppContext';
import { createScaleStream, type ScaleFrame } from '../scale/serial';
import type { AppSettings } from '../types';

const inputClass = 'mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950';
const buttonClass = 'rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-800 disabled:opacity-50 dark:border-rose-800 dark:text-rose-200';
const panelClass = 'space-y-3 rounded-md border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900';

const fields = [
  ['scale_label_enabled', 'scaleLabelEnabled', '0', ['0', '1']],
  ['scale_label_prefixes', 'scalePrefixes', '20', null],
  ['scale_label_plu_length', 'scalePluLength', '5', ['4', '5', '6']],
  ['scale_label_value_length', 'scaleValueLength', '5', ['4', '5', '6']],
  ['scale_label_value_type', 'scaleValueType', 'weight', ['weight', 'price']],
  ['scale_label_value_decimals', 'scaleValueDecimals', '3', ['0', '1', '2', '3']],
  ['scale_unitless_unit', 'scaleUnitless', 'kg', ['kg', 'g']],
  ['scale_data_bits', 'scaleDataBits', '8', ['7', '8']],
  ['scale_parity', 'scaleParity', 'none', ['none', 'even', 'odd']],
  ['scale_stop_bits', 'scaleStopBits', '1', ['1', '2']],
] as const;

type ScaleField = (typeof fields)[number][0];

export function ScaleConfigurationFields({ form, setField }: { form: AppSettings; setField: (key: ScaleField, value: string) => void }) {
  const { t } = useApp();
  return (
    <>
      <p className="text-xs sm:col-span-2">{t('scaleAssumption')}</p>
      {fields.map(([key, label, fallback, options]) => (
        <label key={key} className="text-xs font-semibold">{t(label)}
          {options ? <select className={inputClass} value={form[key] ?? fallback} onChange={event => setField(key, event.target.value)}>
            {options.map(option => <option key={option} value={option}>{key === 'scale_label_enabled' ? t(option === '1' ? 'yes' : 'no') : t(option)}</option>)}
          </select> : <input className={inputClass} dir="ltr" value={form[key] ?? fallback} onChange={event => setField(key, event.target.value)} />}
        </label>
      ))}
      {form.scale_label_value_type === 'price' && <p className="text-xs sm:col-span-2">{t('scalePriceCapacity')}</p>}
    </>
  );
}

type Sample = { code: string; plu: string; weight: string; price: string };
const emptySample: Sample = { code: '', plu: '', weight: '', price: '' };
type Layout = { prefixes: string[]; plu_length: number; value_length: number; type: 'weight' | 'price'; decimals: number };
type Learning = { layouts: Layout[]; can_save: boolean };
type ScaleProduct = { id: number; name_ku: string; scale_product_code: string };
type Catalog = { products: ScaleProduct[]; duplicates: Array<{ plu: string; products: ScaleProduct[] }> };
type Preview = { code: string; plu: string; weight_kg: number; type: 'weight' | 'price'; value: number; name: string };

const checks = ['printed', 'learned', 'half', 'oneQuarter', 'thirdProduct', 'priceMatches', 'saleReturned'] as const;
type Check = (typeof checks)[number];
type Checklist = { items: Check[]; verified: boolean; by?: string; at?: string };

function readChecklist(value?: string): Checklist {
  if (value) {
    try {
      const state = JSON.parse(value) as Checklist;
      if (Array.isArray(state.items)) {
        const items = checks.filter(item => state.items.includes(item));
        return { ...state, items, verified: state.verified === true && items.length === checks.length };
      }
    } catch {
      // Invalid or previous settings have no verified checklist.
    }
  }
  return { items: [], verified: false };
}

const serialSamples = [
  ['ST,GS,+  0.500 kg\r\n', '0.500 kg\n'],
  ['US,GS,+  0.525 kg\r'],
  ['OL,GS,+  0.525 kg\n'],
  ['ST,GS,-  0.525 kg\n'],
  ['0.750\n'],
  ['ST,GS,+  1.', '250 kg\r', '\n'],
  ['0250 g\r', '0 g\n', '0500 g\r'],
];

export function ScaleSettingsTools({ admin }: { admin: boolean }) {
  const { settings, updateSettings, showToast, t } = useApp();
  const [tab, setTab] = useState<'learn' | 'simulation' | 'checklist'>('learn');
  const [catalog, setCatalog] = useState<Catalog>({ products: [], duplicates: [] });
  const [samples, setSamples] = useState<Sample[]>([{ ...emptySample }]);
  const [learning, setLearning] = useState<Learning | null>(null);
  const [product, setProduct] = useState('');
  const [weight, setWeight] = useState('0.525');
  const [preview, setPreview] = useState<Preview | null>(null);
  const [scan, setScan] = useState<Preview | null>(null);
  const [frames, setFrames] = useState<ScaleFrame[]>([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [savedChecks, setSavedChecks] = useState(() => readChecklist(settings.scale_hardware_checklist));
  const [completed, setCompleted] = useState<Check[]>(savedChecks.items);
  const barcode = useRef<SVGSVGElement>(null);

  useEffect(() => {
    let current = true;
    apiRequest<Catalog>('/api/settings/scale').then(result => {
      if (current) setCatalog(result.data);
    }).catch(error => { if (current) setError(error.message); });
    return () => { current = false; };
  }, []);

  useEffect(() => {
    const value = readChecklist(settings.scale_hardware_checklist);
    setSavedChecks(value);
    setCompleted(value.items);
  }, [settings.scale_hardware_checklist]);

  useEffect(() => {
    if (tab === 'simulation' && preview && barcode.current) {
      JsBarcode(barcode.current, preview.code, { format: 'EAN13', width: 2, height: 56, margin: 10 });
    }
  }, [preview, tab]);

  const run = async (action: () => Promise<void>) => {
    setBusy(true);
    setError('');
    try { await action(); }
    catch (error) { setError(error instanceof Error ? error.message : 'error'); }
    finally { setBusy(false); }
  };

  const learn = (confirm: boolean) => run(async () => {
    if (!confirm) setLearning(null);
    const result = await apiRequest<Learning>('/api/settings/scale/learn', { method: 'POST', body: jsonBody({ samples, confirm }) });
    setLearning(result.data);
    if (confirm) {
      const updated = await apiRequest<AppSettings>('/api/settings');
      updateSettings(updated.data);
      showToast(t('saved'), 'success');
      setLearning(null);
    }
  });

  const simulateLabel = (code?: string) => run(async () => {
    const result = await apiRequest<Preview>('/api/settings/scale/preview', {
      method: 'POST', body: jsonBody({ product_id: Number(product), weight, code }),
    });
    if (code) setScan(result.data);
    else { setPreview(result.data); setScan(null); }
  });

  const simulateSerial = () => {
    const parse = createScaleStream(settings.scale_unitless_unit === 'g' ? 'g' : 'kg');
    setFrames(serialSamples.flatMap(chunks => ['0 g\r', ...chunks].flatMap(parse)).filter(frame => frame.raw !== '0 g'));
  };

  const saveChecklist = () => run(async () => {
    const result = await apiRequest<Checklist>('/api/settings/scale/checklist', { method: 'POST', body: jsonBody({ items: completed }) });
    setSavedChecks(result.data);
    updateSettings({ ...settings, scale_hardware_checklist: JSON.stringify(result.data) });
    showToast(t('saved'), 'success');
  });

  return (
    <section className={panelClass} aria-label={t('scaleSettings')}>
      <div className="flex flex-wrap items-center gap-2">
        <button type="button" className={buttonClass} aria-pressed={tab === 'learn'} onClick={() => setTab('learn')}>{t('scaleLearn')}</button>
        {admin && <button type="button" className={buttonClass} aria-pressed={tab === 'simulation'} onClick={() => setTab('simulation')}>{t('scaleSimulation')}</button>}
        {admin && <button type="button" className={buttonClass} aria-pressed={tab === 'checklist'} onClick={() => setTab('checklist')}>{t('scaleChecklist')}</button>}
      </div>
      <p role="status" className="text-sm font-semibold">{t(savedChecks.verified ? 'scaleHardwareVerified' : 'scaleHardwareUnverified')}</p>
      {catalog.duplicates.length > 0 && (
        <aside className="rounded border border-amber-400 p-3 text-sm">
          <h3 className="font-bold">{t('scaleDuplicatePlu')}</h3>
          <ul>{catalog.duplicates.map(group => <li key={group.plu}><code>{group.plu}</code> • {group.products.map(item => item.name_ku).join(', ')}</li>)}</ul>
        </aside>
      )}
      {error && <p role="alert" className="text-sm text-rose-700 dark:text-rose-300">{t(error)}</p>}

      {tab === 'learn' && (
        <div className="space-y-3">
          <p className="text-sm">{t('scaleLearnGuide')}</p>
          {samples.map((sample, index) => <fieldset key={index} className="grid gap-2 sm:grid-cols-4">
            <legend className="text-sm font-semibold">{t('scaleSample')} {index + 1}</legend>
            {(['code', 'plu', 'weight', 'price'] as const).map(key => <label key={key} className="text-xs">{t(`scaleSample_${key}`)}
              <input className={inputClass} dir="ltr" inputMode={key === 'weight' || key === 'price' ? 'decimal' : 'text'} value={sample[key]}
                disabled={busy} onChange={event => {
                  const value = event.target.value;
                  setSamples(current => current.map((item, position) => position === index ? { ...item, [key]: value } : item));
                  setLearning(null);
                  setError('');
                }} />
            </label>)}
          </fieldset>)}
          <div className="flex flex-wrap gap-2">
            <button type="button" className={buttonClass} disabled={busy || samples.length >= 8} onClick={() => { setSamples(current => [...current, { ...emptySample }]); setLearning(null); }}>{t('scaleAddSample')}</button>
            <button type="button" className={buttonClass} disabled={busy || samples.length <= 1} onClick={() => { setSamples(current => current.slice(0, -1)); setLearning(null); }}>{t('scaleRemoveSample')}</button>
            <button type="button" className={buttonClass} disabled={busy} onClick={() => void learn(false)}>{t('scaleFindLayouts')}</button>
          </div>
          {learning && <div className="space-y-2" aria-live="polite">
            {learning.layouts.length ? <ul className="list-inside list-disc text-sm">
              {learning.layouts.map(layout => <li key={JSON.stringify(layout)}>{t('scalePrefixes')} {layout.prefixes.join(', ')} · PLU {layout.plu_length} · {t(layout.type)} {layout.value_length} · {t('scaleValueDecimals')} {layout.decimals}</li>)}
            </ul> : <p>{t('scaleNoMatch')} <code dir="ltr">{samples.map(sample => sample.code).join(' / ')}</code></p>}
            {learning.can_save ? <button type="button" className={buttonClass} disabled={busy} onClick={() => void learn(true)}>{t('scaleConfirmLayout')}</button>
              : <p className="text-xs">{t('scaleCannotConfirm')}</p>}
          </div>}
        </div>
      )}

      {admin && tab === 'simulation' && (
        <div className="space-y-3">
          <p className="text-sm font-semibold">{t('scaleTestOnly')}</p>
          <div className="grid gap-2 sm:grid-cols-2">
            <label className="text-xs">{t('products')}<select className={inputClass} value={product} onChange={event => { setProduct(event.target.value); setPreview(null); setScan(null); }}>
              <option value="">{t('selectWeightedItem')}</option>
              {catalog.products.map(item => <option key={item.id} value={item.id}>{item.name_ku} ({item.scale_product_code})</option>)}
            </select></label>
            <label className="text-xs">{t('scaleSample_weight')}<input className={inputClass} dir="ltr" inputMode="decimal" value={weight} onChange={event => { setWeight(event.target.value); setPreview(null); setScan(null); }} /></label>
          </div>
          <button type="button" className={buttonClass} disabled={busy || !product} onClick={() => void simulateLabel()}>{t('scaleGenerateLabel')}</button>
          {preview && <div>
            <svg ref={barcode} role="img" aria-label={preview.code} className="my-2 max-w-full" />
            <button type="button" className={buttonClass} disabled={busy} onClick={() => void simulateLabel(preview.code)}>{t('scaleScanTest')}</button>
            {scan && <p className="mt-2 text-sm" role="status">{scan.name} · PLU {scan.plu} · {scan.weight_kg.toFixed(3)} kg · {t(scan.type)} {scan.value}</p>}
          </div>}
          <div className="border-t border-slate-200 pt-3 dark:border-slate-700">
            <button type="button" className={buttonClass} onClick={simulateSerial}>{t('scaleSimulateSerial')}</button>
            <ul className="mt-2 text-sm">{frames.map((frame, index) => <li key={index}><code dir="ltr">{frame.raw}</code> · {t(`scaleFrame_${frame.status}`)}{frame.kg !== undefined && ` · ${frame.kg.toFixed(3)} kg`}{frame.accepted && ` · ${t('scaleAcceptedFrame')}`}</li>)}</ul>
          </div>
        </div>
      )}

      {admin && tab === 'checklist' && (
        <div className="space-y-3">
          <p className="text-sm">{t('scaleChecklistGuide')}</p>
          <ul className="space-y-2">{checks.map(item => <li key={item}>
            <label className="flex items-center gap-2 text-sm"><input type="checkbox" disabled={busy} checked={completed.includes(item)} onChange={event => {
              const checked = event.target.checked;
              setCompleted(current => checked ? [...current, item] : current.filter(value => value !== item));
            }} />{t(`scaleCheck_${item}`)}</label>
          </li>)}</ul>
          {savedChecks.by && savedChecks.at && <p className="text-xs">{savedChecks.by} · {new Date(savedChecks.at).toLocaleString()}</p>}
          <button type="button" className={buttonClass} disabled={busy} onClick={() => void saveChecklist()}>{t('save')}</button>
        </div>
      )}
    </section>
  );
}
