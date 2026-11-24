import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { Scale, Plug, PlugZap, CircleCheck, CircleAlert } from 'lucide-react';
import { useApp } from '../context/AppContext';
import { createScaleStream, type ScaleFrame, type UnitlessWeight } from '../scale/serial';

type SerialPortLike = {
  open(options: { baudRate: number; dataBits: 7 | 8; parity: 'none' | 'even' | 'odd'; stopBits: 1 | 2 }): Promise<void>;
  close(): Promise<void>;
  readable: ReadableStream<Uint8Array> | null;
};

type SerialLike = {
  requestPort(options?: { filters?: Array<Record<string, number>> }): Promise<SerialPortLike>;
};

type ScalePanelProps = {
  baudRate: number;
  dataBits: 7 | 8;
  parity: 'none' | 'even' | 'odd';
  stopBits: 1 | 2;
  unitless: UnitlessWeight;
  onWeight: (weightKg: number, source: string) => void;
};

export function ScalePanel({ baudRate, dataBits, parity, stopBits, unitless, onWeight }: ScalePanelProps) {
  const { t } = useApp();
  const [connected, setConnected] = useState(false);
  const [connecting, setConnecting] = useState(false);
  const [reading, setReading] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [frames, setFrames] = useState<ScaleFrame[]>([]);
  const readerRef = useRef<ReadableStreamDefaultReader<Uint8Array> | null>(null);
  const cancellation = useRef<Promise<void> | undefined>(undefined);
  const activeRef = useRef(false);
  const mounted = useRef(false);
  const latestWeight = useRef(onWeight);

  useLayoutEffect(() => { latestWeight.current = onWeight; }, [onWeight]);

  const stop = () => {
    activeRef.current = false;
    setConnecting(true);
    cancellation.current = readerRef.current?.cancel().catch(() => undefined);
  };

  useEffect(() => {
    mounted.current = true;
    return () => {
      mounted.current = false;
      activeRef.current = false;
      cancellation.current ??= readerRef.current?.cancel().catch(() => undefined);
    };
  }, []);

  const connect = async () => {
    const serial = (navigator as Navigator & { serial?: SerialLike }).serial;
    if (!serial) {
      setError(t('scaleNotSupported'));
      return;
    }

    setConnecting(true);
    setError(null);
    setReading(null);
    setFrames([]);
    let port: SerialPortLike | undefined;
    let opened = false;
    try {
      port = await serial.requestPort();
      if (!mounted.current) return;
      await port.open({ baudRate, dataBits, parity, stopBits });
      opened = true;
      if (!mounted.current) return;
      const reader = port.readable?.getReader();
      if (!reader) throw new Error(t('scaleError'));
      readerRef.current = reader;
      activeRef.current = true;
      setConnected(true);
      setConnecting(false);
      const decoder = new TextDecoder();
      const parseChunk = createScaleStream(unitless);

      while (activeRef.current) {
        const { value, done } = await reader.read();
        if (!activeRef.current) break;
        if (done) throw new Error(t('scaleDisconnected'));
        const received = parseChunk(decoder.decode(value, { stream: true }));
        if (received.length) setFrames(current => [...current, ...received].slice(-12));
        for (const frame of received) {
          if (frame.status === 'stable' || frame.status === 'zero') setReading(frame.kg ?? null);
          if (frame.accepted && frame.kg !== undefined) latestWeight.current(frame.kg, 'web-serial');
        }
      }
    } catch (caught) {
      if (mounted.current) setError(caught instanceof Error ? caught.message : t('scaleError'));
    } finally {
      activeRef.current = false;
      await cancellation.current;
      cancellation.current = undefined;
      readerRef.current?.releaseLock();
      readerRef.current = null;
      if (opened) await port?.close().catch(() => undefined);
      if (mounted.current) {
        setConnected(false);
        setConnecting(false);
      }
    }
  };

  return (
    <div className="flex flex-wrap items-center gap-2 rounded-lg border border-rose-200 bg-rose-50/70 px-3 py-2 text-xs dark:border-rose-900/60 dark:bg-rose-950/30">
      <Scale className="size-4 shrink-0 text-rose-700 dark:text-rose-300" />
      <span className="font-semibold text-slate-700 dark:text-slate-200">{t('smartScale')}</span>
      {connected ? (
        <button
          type="button"
          disabled={connecting}
          onClick={stop}
          className="flex items-center gap-1 rounded-md border border-rose-300 px-2 py-1 font-semibold text-rose-700 hover:bg-rose-100 dark:border-rose-800 dark:text-rose-300 dark:hover:bg-rose-900/40"
        >
          <PlugZap className="size-3.5" /> {t('disconnectScale')}
        </button>
      ) : (
        <button
          type="button"
          disabled={connecting}
          onClick={() => void connect()}
          className="flex items-center gap-1 rounded-md bg-rose-700 px-2 py-1 font-semibold text-white disabled:opacity-60"
        >
          <Plug className="size-3.5" /> {connecting ? t('loading') : t('connectScale')}
        </button>
      )}
      {reading !== null && (
        <span className="flex items-center gap-1 font-bold text-emerald-700 dark:text-emerald-300">
          <CircleCheck className="size-3.5" /> {reading.toFixed(3)} kg
        </span>
      )}
      {error && (
        <span className="flex items-center gap-1 font-normal text-rose-600 dark:text-rose-300">
          <CircleAlert className="size-3.5" /> {error}
        </span>
      )}
      {frames.length > 0 && (
        <details className="basis-full">
          <summary>{t('scaleDiagnostics')}</summary>
          <ul className="mt-1 max-h-36 overflow-auto">
            {frames.map((frame, index) => <li key={index}><code dir="ltr">{frame.raw}</code> · {t(`scaleFrame_${frame.status}`)}{frame.kg !== undefined && ` · ${frame.kg.toFixed(3)} kg`}</li>)}
          </ul>
        </details>
      )}
    </div>
  );
}
