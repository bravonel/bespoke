<?php

namespace Tests\Feature;

use Tests\TestCase;

class NativeBrowserDialogTest extends TestCase
{
    public function test_application_ui_does_not_use_native_browser_dialogs(): void
    {
        $files = array_merge(
            glob(resource_path('js/*.js')) ?: [],
            $this->bladeFiles(resource_path('views')),
        );

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $relativePath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);

            $this->assertDoesNotMatchRegularExpression(
                '/window\.(alert|confirm|prompt)\s*\(/',
                $contents,
                "Native browser dialog found in {$relativePath}.",
            );
            $this->assertDoesNotMatchRegularExpression(
                '/return\s+confirm\s*\(/',
                $contents,
                "Inline browser confirmation found in {$relativePath}.",
            );
        }
    }

    public function test_layout_contains_the_shared_interaction_dialog(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $component = file_get_contents(resource_path('views/components/interaction-dialog.blade.php'));
        $javascript = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString('<x-interaction-dialog />', $layout);
        $this->assertStringContainsString('role="dialog"', $component);
        $this->assertStringContainsString('aria-modal="true"', $component);
        $this->assertStringContainsString('window.bespokeDialog', $javascript);
    }

    /**
     * @return array<int, string>
     */
    private function bladeFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
