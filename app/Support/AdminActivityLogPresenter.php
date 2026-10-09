<?php

namespace App\Support;

use App\Models\AdminActivityLog;

/**
 * 将操作日志中的动作、页面、目标和详情转成可读文案。
 */
final class AdminActivityLogPresenter
{
    /**
     * @return array{action:string,page:string,method:string,target:string,details:string}
     */
    public static function present(AdminActivityLog $log): array
    {
        return [
            'action' => self::actionLabel($log),
            'page' => self::pageLabel($log),
            'method' => self::methodLabel($log),
            'target' => self::targetLabel($log),
            'details' => self::detailsLabel($log),
        ];
    }

    public static function actionLabel(AdminActivityLog $log): string
    {
        $raw = trim((string) ($log->action ?? ''));
        if ($raw === '') {
            return self::dash();
        }

        $actions = self::group('actions');
        if (isset($actions[$raw])) {
            return (string) $actions[$raw];
        }

        [$routeName, $formAction] = self::splitAction($raw);

        foreach (self::candidates($routeName) as $candidate) {
            if (isset($actions[$candidate])) {
                return (string) $actions[$candidate];
            }
        }

        foreach (self::tails($routeName) as $tail) {
            if (isset($actions[$tail])) {
                return (string) $actions[$tail];
            }
        }

        if ($formAction !== '' && isset($actions[$formAction])) {
            return (string) $actions[$formAction];
        }

        return $raw;
    }

    public static function pageLabel(AdminActivityLog $log): string
    {
        $pages = self::group('pages');
        [$routeName] = self::splitAction(trim((string) ($log->action ?? '')));

        foreach (self::candidates($routeName) as $candidate) {
            if (isset($pages[$candidate])) {
                return (string) $pages[$candidate];
            }
        }

        $page = trim((string) ($log->page ?? ''));
        if ($page !== '' && isset($pages[$page])) {
            return (string) $pages[$page];
        }

        $actions = self::group('actions');
        if ($page !== '' && isset($actions[$page])) {
            return (string) $actions[$page];
        }

        return $page !== '' ? $page : self::dash();
    }

    public static function methodLabel(AdminActivityLog $log): string
    {
        $method = strtoupper(trim((string) ($log->request_method ?? '')));
        if ($method === '') {
            return self::dash();
        }

        $methods = self::group('methods');

        return (string) ($methods[$method] ?? $method);
    }

    public static function targetLabel(AdminActivityLog $log): string
    {
        $type = trim((string) ($log->target_type ?? ''));
        if ($type === '') {
            return self::dash();
        }

        $targets = self::group('targets');
        $typeLabel = (string) ($targets[$type] ?? $type);
        $targetId = $log->target_id;

        if ($targetId === null || (int) $targetId <= 0) {
            return $typeLabel;
        }

        $template = self::string('target_with_id', ':type #:id');

        return str_replace(
            [':type', ':id'],
            [$typeLabel, (string) ((int) $targetId)],
            $template
        );
    }

    public static function detailsLabel(AdminActivityLog $log): string
    {
        $raw = trim((string) ($log->details ?? ''));
        if ($raw === '') {
            return self::dash();
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $formatted = trim(self::formatDetails($decoded));

            return $formatted !== '' ? $formatted : self::dash();
        }

        return self::formatScalar($raw);
    }

    /**
     * @param  array<string|int, mixed>  $details
     */
    private static function formatDetails(array $details, int $depth = 0): string
    {
        $hiddenKeys = ['_token', '_method', 'action'];
        $lines = [];

        foreach ($details as $key => $value) {
            $field = (string) $key;
            if (in_array($field, $hiddenKeys, true)) {
                continue;
            }

            $label = self::fieldLabel($field);
            if (is_array($value)) {
                $nested = trim(self::formatDetails($value, $depth + 1));
                if ($nested === '') {
                    continue;
                }

                $lines[] = $label.self::string('label_separator', '：');
                foreach (explode("\n", $nested) as $nestedLine) {
                    $lines[] = '  '.$nestedLine;
                }

                continue;
            }

            $formatted = self::formatScalar($value);
            if ($formatted === '') {
                continue;
            }

            $lines[] = $label.self::string('label_separator', '：').$formatted;
        }

        return implode("\n", $lines);
    }

    private static function formatScalar(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? self::string('yes', '是') : self::string('no', '否');
        }

        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        if ($text === '[redacted]') {
            return self::string('redacted', '已隐藏');
        }

        if (preg_match('/^\[text:(\d+)\s+chars\]$/i', $text, $matches) === 1) {
            return str_replace(':count', $matches[1], self::string('text_chars', '文本（:count字）'));
        }

        $values = self::group('values');
        $lookup = strtolower($text);
        if (isset($values[$lookup])) {
            return (string) $values[$lookup];
        }

        return $text;
    }

    private static function fieldLabel(string $field): string
    {
        $fields = self::group('fields');
        if (isset($fields[$field])) {
            return (string) $fields[$field];
        }

        return $field;
    }

    /**
     * @return array{0:string,1:string}
     */
    private static function splitAction(string $action): array
    {
        if ($action === '' || ! str_contains($action, ':')) {
            return [$action, ''];
        }

        [$left, $right] = explode(':', $action, 2);
        if (str_contains($left, '.') || str_starts_with($left, 'admin')) {
            return [$left, $right];
        }

        return [$action, $right];
    }

    /**
     * @return list<string>
     */
    private static function candidates(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return [];
        }

        $values = [$name];
        $stripped = preg_replace('/^admin\./', '', $name) ?? $name;
        if ($stripped !== $name) {
            $values[] = $stripped;
        }

        $parts = explode('.', $stripped);
        while (count($parts) > 1) {
            array_pop($parts);
            $values[] = implode('.', $parts);
        }

        return array_values(array_unique($values));
    }

    /**
     * @return list<string>
     */
    private static function tails(string $name): array
    {
        $stripped = preg_replace('/^admin\./', '', trim($name)) ?? trim($name);
        if ($stripped === '') {
            return [];
        }

        $parts = explode('.', $stripped);
        $tails = [];
        for ($i = 1; $i <= min(3, count($parts)); $i++) {
            $tails[] = implode('.', array_slice($parts, -$i));
        }

        return $tails;
    }

    /**
     * @return array<string, string>
     */
    private static function group(string $group): array
    {
        $value = __('admin.activity_logs.display.'.$group);

        if (! is_array($value)) {
            return [];
        }

        $map = [];
        foreach ($value as $key => $label) {
            if (is_scalar($label) || $label === null) {
                $map[(string) $key] = (string) $label;
            }
        }

        return $map;
    }

    private static function string(string $key, string $fallback): string
    {
        $translationKey = 'admin.activity_logs.display.'.$key;
        $translated = __($translationKey);

        return $translated === $translationKey ? $fallback : (string) $translated;
    }

    private static function dash(): string
    {
        return self::string('none', '-');
    }
}
