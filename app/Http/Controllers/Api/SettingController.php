<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\ScaleLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $settings = Setting::pluck('key_value', 'key_name')->all() + ScaleLabel::DEFAULTS;
        unset($settings['tax_percentage']);
        if (! $request->user()?->hasPermission('settings.manage')) {
            unset($settings['scale_hardware_checklist']);
        } else {
            $settings['scale_hardware_checklist'] ??= '';
        }

        return response()->json(['success' => true, 'data' => $settings]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()?->hasPermission('settings.manage'), 403, 'You are not authorized to manage settings.');

        $request->validate([
            'expiry_alerts_enabled' => 'required|boolean',
            'expiry_warning_days' => 'required|integer|in:3,7,15,30',
            'expiry_urgent_days' => 'required|integer|in:1,3,7,15,30',
            'expiry_alerts_expired' => 'required|boolean',
            'expiry_alerts_bell' => 'required|boolean',
            'expiry_alerts_dashboard' => 'required|boolean',
            'scale_enabled' => 'nullable|boolean',
            'scale_mode' => 'nullable|in:manual,web_serial',
            'scale_port' => 'nullable|string|max:100',
            'scale_baud_rate' => 'nullable|integer|in:1200,2400,4800,9600,19200,38400,57600,115200',
            'weight_decimals' => 'nullable|integer|in:2,3',
            'receipt_width' => 'nullable|in:58mm,80mm,a4',
            'scale_label_enabled' => 'sometimes|boolean',
            'scale_label_prefixes' => 'sometimes|required|string|max:100|regex:/^[0-9]{1,3}(\s*,\s*[0-9]{1,3})*$/D',
            'scale_label_plu_length' => 'sometimes|integer|in:4,5,6',
            'scale_label_value_length' => 'sometimes|integer|in:4,5,6',
            'scale_label_value_type' => 'sometimes|in:weight,price',
            'scale_label_value_decimals' => 'sometimes|integer|between:0,3',
            'scale_unitless_unit' => 'sometimes|in:kg,g',
            'scale_data_bits' => 'sometimes|integer|in:7,8',
            'scale_parity' => 'sometimes|in:none,even,odd',
            'scale_stop_bits' => 'sometimes|integer|in:1,2',
        ]);
        $data = $request->except(['_token', '_method']);

        if ((int) $data['expiry_urgent_days'] > (int) $data['expiry_warning_days']) {
            return response()->json([
                'success' => false,
                'message' => 'Urgent warning period cannot exceed the general warning period.',
                'errors' => ['expiry_urgent_days' => ['Urgent warning period cannot exceed the general warning period.']],
            ], 422);
        }

        $allowedSettings = [
            'store_name', 'store_name_en', 'store_name_ar', 'store_phone', 'store_address',
            'currency', 'invoice_prefix', 'receipt_footer', 'default_language',
            'allow_negative_stock', 'low_stock_threshold', 'expiry_alerts_enabled',
            'expiry_warning_days', 'expiry_urgent_days', 'expiry_alerts_expired',
            'expiry_alerts_bell', 'expiry_alerts_dashboard',
            'scale_enabled', 'scale_mode', 'scale_port', 'scale_baud_rate',
            'weight_decimals', 'receipt_width',
            ...array_keys(ScaleLabel::DEFAULTS),
        ];

        DB::transaction(function () use ($data, $allowedSettings) {
            $previous = Setting::whereIn('key_name', array_keys(ScaleLabel::DEFAULTS))->lockForUpdate()->pluck('key_value', 'key_name')->all() + ScaleLabel::DEFAULTS;
            $scale = array_intersect_key($data, ScaleLabel::DEFAULTS) + $previous;
            $layout = ScaleLabel::layout($scale);
            if (! ScaleLabel::validLayout($layout)) {
                throw ValidationException::withMessages(['scale_label_prefixes' => ['scaleInvalidLayout']]);
            }
            if ($layout !== ScaleLabel::layout($previous)) {
                ScaleLabelController::clearChecklist();
            }
            foreach (array_intersect_key($data, array_flip($allowedSettings)) as $key => $value) {
                $value = match ($key) {
                    'scale_label_prefixes' => implode(',', $layout['prefixes']),
                    'scale_label_enabled' => (string) (int) $value,
                    default => (string) $value,
                };
                Setting::updateOrCreate(['key_name' => $key], [
                    'key_value' => $value,
                    'group_name' => str_starts_with($key, 'expiry_') ? 'expiry'
                        : (str_starts_with($key, 'scale_') || in_array($key, ['weight_decimals', 'receipt_width'], true) ? 'scale' : 'general'),
                ]);
            }
            ActivityLog::log('UPDATE', 'SETTING', 0, 'نوێکردنەوەی رێکخستنەکانی سیستەم');
        });

        $updated = Setting::pluck('key_value', 'key_name')->all() + ScaleLabel::DEFAULTS;
        $updated['scale_hardware_checklist'] ??= '';
        unset($updated['tax_percentage']);

        return response()->json(['success' => true, 'message' => 'Settings saved successfully', 'data' => $updated]);
    }
}
