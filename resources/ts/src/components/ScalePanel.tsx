import { useEffect, useRef, useState } from 'react';
import { Scale, Plug, PlugZap, CircleCheck, CircleAlert } from 'lucide-react';
import { useApp } from '../context/AppContext';

type SerialPortLike = {
  open(options: { baudRate: number }): Promise<void>;
  close(): Promise<void>;
  readable: ReadableStream<Uint8Array> | null;
};

type SerialLike = {
  requestPort(options?: { filters?: Array<Record<string, number>> }): Promise<SerialPortLike>;
};

type ScalePanelProps = {
  baudRate: number;
  onWeight: (weightKg: number, source: string) => void;
};

/** Parses common digital-scale frames such as `ST,GS,+  0.500 kg` or `0250 g`. */
export function parseScaleLine(line: string): { kg: number } | null {
  const text = line.replace(/\s+/g, ' ').trim();
  if (!text) return null;

  const withUnit = text.match(/([+-]?\d+(?:[.,]\d+)?)\s*(kg|kgl|gm|gr|g)\b/i);
  if (withUnit?.[1] && withUnit[2]) {
    const value = Number.parseFloat(withUnit[1].replace(',', '.'));
    if (!Number.isFinite(value)) return null;
    const unit = withUnit[2].toLowerCase();
    return { kg: unit.startsWith('kg') ? value : value / 1000 };
  }

  const plain = text.match(/^([+-]?\d+(?:[.,]\d+)?)$/);
  if (plain?.[1]) {
    const value = Number.parseFloat(plain[1].replace(',', '.'));
    return Number.isFinite(value) ? { kg: value / 1000 } : null;
  }

  return null;
}

export function ScalePanel({ baudRate, onWeight }: ScalePanelProps) {
  const { t } = useApp();
  const [connected, setConnected] = useState(false);
  const [connecting, setConnecting] = useState(false);
  const [reading, setReading] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const portRef = useRef<SerialPortLike | null>(null);
  const activeRef = useRef(false);
  const lastReadingRef = useRef<number | null>(null);
  const stableCountRef = useRef(0);

  const stop = async () => {
    activeRef.current = false;
    const port = portRef.current;
    portRef.current = null;
    setConnected(false);
    setConnecting(false);
    if (port) {
      try {
        await port.close();
      } catch {
        // Port may already be closed by the browser.
      }
    }
  };

  useEffect(() => () => {
    activeRef.current = false;
    void portRef.current?.close().catch(() => undefined);
  }, []);

  const connect = async () => {
    const serial = (navigator as Navigator & { serial?: SerialLike }).serial;
    if (!serial) {
      setError(t('scaleNotSupported'));
      return;
    }

    setConnecting(true);
    setError(null);
    try {
      const port = await serial.requestPort();
      await port.open({ baudRate });
      portRef.current = port;
      activeRef.current = true;
      setConnected(true);

      const reader = port.readable?.getReader();
      if (!reader) throw new Error(t('scaleError'));
      const decoder = new TextDecoder();
      let buffer = '';

      while (activeRef.current) {
        const { value, done } = await reader.read();
        if (done) break;
        buffer += decoder.decode(value, { stream: true });
        const lines = buffer.split(/\r?\n/);
        buffer = lines.pop() ?? '';

        for (const line of lines) {
          const parsed = parseScaleLine(line);
          if (!parsed || parsed.kg <= 0) continue;
          const kg = Math.round(parsed.kg * 1000) / 1000;
          setReading(kg);

          const previous = lastReadingRef.current;
          stableCountRef.current = previous !== null && Math.abs(previous - kg) < 0.0005
            ? stableCountRef.current + 1
            : 0;
          lastReadingRef.current = kg;

          if (stableCountRef.current >= 1) {
            stableCountRef.current = 0;
            onWeight(kg, 'web-serial');
          }
        }
      }
      reader.releaseLock();
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : t('scaleError'));
      await stop();
    } finally {
      setConnecting(false);
    }
  };

  return (
    <div className="flex flex-wrap items-center gap-2 rounded-lg border border-rose-200 bg-rose-50/70 px-3 py-2 text-xs dark:border-rose-900/60 dark:bg-rose-950/30">
      <Scale className="size-4 shrink-0 text-rose-700 dark:text-rose-300" />
      <span className="font-semibold text-slate-700 dark:text-slate-200">{t('smartScale')}</span>
      {connected ? (
        <button
          type="button"
          onClick={() => void stop()}
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
    </div>
  );
}
