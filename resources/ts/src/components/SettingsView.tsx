import { useEffect, useState, type FormEvent } from 'react';
import { Save, Scale, Settings } from 'lucide-react';
import { apiRequest, jsonBody } from '../api';
import { useApp } from '../context/AppContext';
import type { AppSettings } from '../types';
import { NumericInput } from './NumericInput';

const SETTING_FIELDS: Array<{ key: keyof AppSettings; label: string; type?: string; placeholder?: string }> = [
  { key: 'store_name', label: 'storeName', placeholder: 'My Butcher Shop' },
  { key: 'store_name_en', label: 'storeNameEn', placeholder: 'My Butcher Shop' },
  { key: 'store_name_ar', label: 'storeNameAr', placeholder: 'مجزري' },
  { key: 'store_phone', label: 'phone', type: 'tel', placeholder: '+964 750 000 0000' },
  { key: 'store_address', label: 'address', placeholder: 'Main Street, Erbil' },
  { key: 'currency', label: 'currency', placeholder: 'IQD' },
  { key: 'invoice_prefix', label: 'invoicePrefix', placeholder: 'INV' },
  { key: 'receipt_footer', label: 'receiptFooter', placeholder: 'Thank you for visiting - fresh cuts, trusted quality' },
  { key: 'low_stock_threshold', label: 'lowStockThreshold', type: 'number', placeholder: '10' },
];

export function SettingsView() {
  const { t, settings, updateSettings, showToast, theme, setTheme, language, setLanguage } = useApp();
  const [form, setForm] = useState<AppSettings>(settings);
  const [saving, setSaving] = useState(false);

  useEffect(() => setForm(settings), [settings]);

  const saveSettings = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault(); setSaving(true);
    try {
      const result = await apiRequest<AppSettings>('/api/settings', { method: 'POST', body: jsonBody(form) });
      updateSettings(result.data);
      showToast(t('saved'), 'success');
    } catch (error) { showToast(error instanceof Error ? error.message : t('error'), 'error'); }
    finally { setSaving(false); }
  };

  return (
    <section className="space-y-4">
      <div className="flex items-center gap-2"><Settings className="size-5 text-rose-700" /><h1 className="text-xl font-bold text-slate-900 dark:text-white">{t('settings')}</h1></div>
      <form onSubmit={event => void saveSettings(event)} className="rounded-md border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div className="border-b border-slate-200 px-4 py-3 dark:border-slate-800"><h2 className="text-sm font-bold text-slate-900 dark:text-white">{t('storeInformation')}</h2></div>
        <div className="grid gap-4 p-4 sm:grid-cols-2">{SETTING_FIELDS.map(({ key, label, type, placeholder }) => <label key={key} className="text-xs font-semibold text-slate-700 dark:text-slate-300">{t(label)}{type === 'number' ? <NumericInput min="0" value={form[key] ?? ''} onValueChange={value => setForm(current => ({ ...current, [key]: value }))} placeholder={placeholder} className="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-normal dark:border-slate-700 dark:bg-slate-950 dark:text-white" /> : <input type={type || 'text'} value={form[key]} onChange={event => setForm(current => ({ ...current, [key]: event.target.value }))} placeholder={placeholder} className="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-normal dark:border-slate-700 dark:bg-slate-950 dark:text-white" />}</label>)}
          <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">{t('defaultLanguage')}<select value={form.default_language} onChange={event => setForm(current => ({ ...current, default_language: event.target.value }))} className="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"><option value="ku">کوردی</option><option value="en">English</option><option value="ar">العربية</option></select></label>
          <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">{t('allowNegativeStock')}<select value={form.allow_negative_stock} onChange={event => setForm(current => ({ ...current, allow_negative_stock: event.target.value }))} className="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"><option value="0">{t('no')}</option><option value="1">{t('yes')}</option></select></label>
        </div>
        <div className="border-y border-slate-200 px-4 py-3 dark:border-slate-800">
          <h2 className="text-sm font-bold text-slate-900 dark:text-white">{t('expiryAlerts')} — ئاگادارکردنەوەی بەسەرچوونی ماددەکان</h2>
          <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{t('expiryAlertsDescription')}</p>
        </div>
        <div className="grid gap-4 p-4 sm:grid-cols-2">
          {([
            ['expiry_alerts_enabled', 'expiryAlertsEnabled'],
            ['expiry_alerts_expired', 'expiryExpiredEnabled'],
            ['expiry_alerts_bell', 'expiryBellEnabled'],
            ['expiry_alerts_dashboard', 'expiryDashboardEnabled'],
          ] as const).map(([key, label]) => (
            <label key={key} className="text-xs font-semibold text-slate-700 dark:text-slate-300">
              {t(label)}
              <select
                value={form[key]}
                onChange={event => setForm(current => ({ ...current, [key]: event.target.value }))}
                className="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"
              >
                <option value="1">{t('yes')}</option>
                <option value="0">{t('no')}</option>
              </select>
            </label>
          ))}
          <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
            {t('expiryWarningDays')}
            <select
              value={form.expiry_warning_days}
              onChange={event => setForm(current => ({
                ...current,
                expiry_warning_days: event.target.value,
                expiry_urgent_days: Math.min(Number(current.expiry_urgent_days), Number(event.target.value)).toString(),
              }))}
              className="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"
            >
              {[3, 7, 15, 30].map(days => <option key={days} value={days}>{days} {t('days')}</option>)}
            </select>
          </label>
          <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">
            {t('expiryUrgentDays')}
            <select
              value={form.expiry_urgent_days}
              onChange={event => setForm(current => ({ ...current, expiry_urgent_days: event.target.value }))}
              className="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"
            >
              {[1, 3, 7, 15, 30].filter(days => days <= Number(form.expiry_warning_days)).map(days => (
                <option key={days} value={days}>{days} {t('days')}</option>
              ))}
            </select>
          </label>
        </div>
        <div className="border-y border-slate-200 px-4 py-3 dark:border-slate-800">
          <div className="flex items-center gap-2"><Scale className="size-4 text-rose-700" /><h2 className="text-sm font-bold text-slate-900 dark:text-white">{t('scaleSettings')}</h2></div>
          <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{t('smartScale')} - Web Serial / manual</p>
        </div>
        <div className="grid gap-4 p-4 sm:grid-cols-2">
          <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">{t('scaleEnabled')}<select value={form.scale_enabled ?? '0'} onChange={event => setForm(current => ({ ...current, scale_enabled: event.target.value }))} className="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"><option value="0">{t('no')}</option><option value="1">{t('yes')}</option></select></label>
          <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">{t('scaleMode')}<select value={form.scale_mode ?? 'manual'} onChange={event => setForm(current => ({ ...current, scale_mode: event.target.value }))} className="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"><option value="manual">{t('useManual')}</option><option value="web_serial">{t('useWebSerial')}</option></select></label>
          <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">{t('scalePort')}<input type="text" value={form.scale_port ?? ''} onChange={event => setForm(current => ({ ...current, scale_port: event.target.value }))} placeholder="COM3" className="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-normal dark:border-slate-700 dark:bg-slate-950 dark:text-white" /></label>
          <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">{t('scaleBaudRate')}<select value={form.scale_baud_rate ?? '9600'} onChange={event => setForm(current => ({ ...current, scale_baud_rate: event.target.value }))} className="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">{[1200, 2400, 4800, 9600, 19200, 38400, 57600, 115200].map(rate => <option key={rate} value={rate}>{rate}</option>)}</select></label>
          <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">{t('weightDecimals')}<select value={form.weight_decimals ?? '3'} onChange={event => setForm(current => ({ ...current, weight_decimals: event.target.value }))} className="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"><option value="2">2</option><option value="3">3</option></select></label>
          <label className="text-xs font-semibold text-slate-700 dark:text-slate-300">{t('receiptWidth')}<select value={form.receipt_width ?? '80mm'} onChange={event => setForm(current => ({ ...current, receipt_width: event.target.value }))} className="mt-1.5 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"><option value="58mm">58mm</option><option value="80mm">80mm</option><option value="a4">A4</option></select></label>
          <p className="text-[11px] leading-relaxed text-slate-500 sm:col-span-2 dark:text-slate-400">{t('scalePortNote')} {t('scaleBrowserNote')}</p>
        </div>
        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 dark:border-slate-800"><div className="flex items-center gap-3 text-xs"><label className="flex items-center gap-2">{t('theme')}<select value={theme} onChange={event => setTheme(event.target.value as 'light' | 'dark')} className="rounded-md border border-slate-300 bg-white px-2 py-1.5 dark:border-slate-700 dark:bg-slate-950"><option value="light">{t('light')}</option><option value="dark">{t('dark')}</option></select></label><label className="flex items-center gap-2">{t('language')}<select value={language} onChange={event => setLanguage(event.target.value as 'ku' | 'en' | 'ar')} className="rounded-md border border-slate-300 bg-white px-2 py-1.5 dark:border-slate-700 dark:bg-slate-950"><option value="ku">کوردی</option><option value="en">English</option><option value="ar">العربية</option></select></label></div><button disabled={saving} className="flex items-center gap-2 rounded-md bg-rose-700 px-4 py-2 text-xs font-bold text-white disabled:opacity-60"><Save className="size-4" />{saving ? t('loading') : t('save')}</button></div>
      </form>
    </section>
  );
}