<?php

namespace Translate5\MaintenanceCli\Command\Log;

use editor_Utils;
use Symfony\Component\Console\Formatter\OutputFormatter;
use ZfExtended_Diff;

readonly class EcodeSpecificFormatter
{
    private const string TYPE_FULL_BREAK = 'fullBreak';

    private const string TYPE_WORD_BREAK = 'wordBreak';

    private const string TYPE_TAG_BREAK = 'tagBreak';

    protected function __construct(
        private bool $enabled,
        private ZfExtended_Diff $diff
    ) {
    }

    public static function create(bool $enabled): EcodeSpecificFormatter
    {
        return new self(
            $enabled,
            new ZfExtended_Diff(),
        );
    }

    public function formatExtra(mixed $extra, string $eventCode): string
    {
        $formattedOutput = '';
        if (is_array($extra)) {
            switch ($eventCode) {
                case 'E1764':
                    $formattedOutput = 'Diff: ' . PHP_EOL . $this->segmentContentWordDiff(
                        $extra['input'] ?? '',
                        $extra['cleaned'] ?? '',
                        self::TYPE_WORD_BREAK
                    );

                    break;
                default:
                    break;
            }
        }
        $extra = json_encode($extra, JSON_PRETTY_PRINT);

        return OutputFormatter::escape((string) $extra) . ' ' . $formattedOutput;
    }

    protected function segmentContentWordDiff(string $old, string $new, $type = self::TYPE_TAG_BREAK): string
    {
        if (! $this->enabled) {
            return $new;
        }

        [$old, $new] = $this->tokenizer([$old, $new], $type);

        $diff = $this->diff->process($old, $new);

        $out = '';
        foreach ($diff as $part) {
            if (is_array($part)) {
                foreach ($part['d'] as $token) {
                    $out .= '<fg=red>' . OutputFormatter::escape($token) . '</>';
                }
                foreach ($part['i'] as $token) {
                    $out .= '<fg=green>' . OutputFormatter::escape($token) . '</>';
                }
            } else {
                $out .= OutputFormatter::escape($part);
            }
        }

        return $out;
    }

    private function tokenizer(array $data, string $type): array
    {
        foreach ($data as $idx => $value) {
            switch ($type) {
                case self::TYPE_FULL_BREAK:
                    $data[$idx] = editor_Utils::wordBreakUp(editor_Utils::tagBreakUp($value));

                    break;
                case self::TYPE_WORD_BREAK:
                    $data[$idx] = editor_Utils::wordBreakUp([$value]);

                    break;
                case self::TYPE_TAG_BREAK:
                    $data[$idx] = editor_Utils::tagBreakUp($value);

                    break;
                default:
                    break;
            }
        }

        return $data;
    }
}
