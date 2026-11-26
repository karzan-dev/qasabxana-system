import { useState } from 'react';
import { Database, Download, LoaderCircle, ShieldCheck } from 'lucide-react';
import { apiFetch } from '../api';
import { useApp } from '../context/AppContext';

export function BackupView() {
  const { t, showToast } = useApp();
  const [downloading, setDownloading] = useState(false);

  const downloadBackup = async () => {
    setDownloading(true);
    try {
      const response = await apiFetch('/api/backup');
      if (!response.ok) {
        const payload = await response.json().catch(() => null) as { message?: string } | null;
        throw new Error(payload?.message || `Request failed (${response.status})`);
      }

      const backup = await response.blob();
      const url = URL.createObjectURL(backup);
      const link = document.createElement('a');
      link.href = url;
      link.download = `qasabxana-butcher-backup-${new Date().toISOString().slice(0, 10)}.sql`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.setTimeout(() => URL.revokeObjectURL(url), 1000);
      showToast(t('backupReady'), 'success');
    } catch (error) {
      showToast(error instanceof Error ? error.message : t('error'), 'error');
    } finally {
      setDownloading(false);
    }
  };

  return (
    <section className="min-w-0 space-y-4 sm:space-y-5">
      <header className="flex items-start gap-3">
        <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300">
          <Database className="size-5" aria-hidden="true" />
        </span>
        <div className="min-w-0 break-words">
          <h1 className="text-xl font-bold text-slate-900 dark:text-white">{t('backup')}</h1>
          <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{t('backupDescription')}</p>
        </div>
      </header>

      <div className="min-w-0 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
        <div className="flex items-start gap-3">
          <ShieldCheck className="mt-0.5 size-5 shrink-0 text-emerald-600" aria-hidden="true" />
          <p className="min-w-0 [overflow-wrap:anywhere] text-sm leading-relaxed text-slate-600 dark:text-slate-300">{t('backupSecurityNote')}</p>
        </div>
        <p className="mt-3 [overflow-wrap:anywhere] text-sm leading-relaxed text-slate-600 dark:text-slate-300">{t('backupImportInstruction')}</p>
        <button
          type="button"
          onClick={() => void downloadBackup()}
          disabled={downloading}
          className="mt-5 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-rose-700 px-3 py-2.5 text-center text-sm font-bold text-white hover:bg-rose-800 disabled:cursor-wait disabled:opacity-60 sm:w-auto sm:px-4"
        >
          {downloading ? <LoaderCircle className="size-4 shrink-0 animate-spin" aria-hidden="true" /> : <Download className="size-4 shrink-0" aria-hidden="true" />}
          {downloading ? t('loading') : t('createBackup')}
        </button>
      </div>
    </section>
  );
}
