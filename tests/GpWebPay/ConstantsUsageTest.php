<?php

namespace Granam\Tests\GpWebPay;

use Granam\GpWebPay\CardPayRequestValues;
use Granam\GpWebPay\Codes\Codes;
use Granam\GpWebPay\SettingsInterface;
use PHPUnit\Framework\TestCase;

class ConstantsUsageTest extends TestCase
{
    /**
     * @test
     */
    public function everyCodeIsTakenFromHelperAsConstant()
    {
        foreach ($this->getProjectNonCodeClasses() as $projectNonCodeClass) {
            if (is_a($projectNonCodeClass, Codes::class, true)) {
                continue; // Codes are the only ones with GP WebPay constants
            }
            $reflectionClass = new \ReflectionClass($projectNonCodeClass);
            $classContent = file_get_contents($reflectionClass->getFileName());
            preg_match_all('~([\'"])(?<CONSTANT_LIKE>[A-Z][A-Z_]+)\1~', $classContent, $matches);
            $constantLikes = array_unique($matches['CONSTANT_LIKE']);
            $constantLikes = array_diff(
                $constantLikes,
                [ // white-list
                    CardPayRequestValues::PRICE_INDEX, 'HTTP_X_FORWARDED_PROTO', 'HTTPS', 'REQUES_SCHEME', 'SERVER_NAME',
                    'SERVER_PORT', 'REQUEST_URI',
                ]
            );
            $constantLikeCount = count($constantLikes);
            self::assertSame(
                0,
                $constantLikeCount,
                "Class {$projectNonCodeClass} uses an internal constant-like values: "
                . implode(
                    ';',
                    array_map(
                        static function (string $constantLike) {
                            return "'{$constantLike}'";
                        },
                        $constantLikes
                    )
                ) . '.'
                . " Every 'CODE_NAME' should be taken from one of " . implode(', ', $this->getCodeClasses())
            );
        }
    }

    /**
     * @return array|string[]
     */
    private function getProjectClasses(): array
    {
        $getClassesFromDir = static function (string $directory, string $rootNamespace) use (&$getClassesFromDir) {
            $classes = [];
            foreach (scandir($directory, SCANDIR_SORT_NONE) as $folder) {
                if (in_array($folder, ['.', '..'], true)) {
                    continue;
                }
                $folderPath = rtrim($directory, '\\/') . DIRECTORY_SEPARATOR . $folder;
                if (is_dir($folderPath)) {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $classes = array_merge($classes, $getClassesFromDir($folderPath, $rootNamespace . '\\' . $folder));
                    continue;
                }
                $class = $rootNamespace . '\\' . basename($folder, '.php');
                self::assertTrue(
                    class_exists($class) || interface_exists($class),
                    "Class nor interface {$class} does not exist or can not be auto-loaded"
                );
                $classes[] = $class;
            }

            return $classes;
        };

        return $getClassesFromDir(
            __DIR__ . '/../../GpWebPay',
            (new \ReflectionClass(SettingsInterface::class))->getNamespaceName()
        );
    }

    /**
     * @return array|string[]
     */
    private function getProjectNonCodeClasses(): array
    {
        return array_filter($this->getProjectClasses(), static function (string $projectClass) {
            return !is_a($projectClass, Codes::class) && $projectClass !== FlatReportParser::class;
        });
    }

    /**
     * @return array|string[]
     */
    private function getCodeClasses(): array
    {
        $codeClasses = [];
        foreach ($this->getProjectClasses() as $projectClass) {
            if (is_a($projectClass, Codes::class, true)) {
                $codeClasses[] = $projectClass;
            }
        }
        self::assertNotEmpty($codeClasses, 'No code classes found');

        return $codeClasses;
    }
}
