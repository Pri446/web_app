<?php

namespace Tests\Unit;

use Tests\TestCase;

class CwisEquityDatabaseLayoutTest extends TestCase
{
    public function test_nested_equity_migrations_are_discovered_by_the_default_migrator(): void
    {
        $path = database_path('migrations/cwis/equity');
        $migrator = app('migrator');
        $this->assertContains($path, $migrator->paths());
        $files = $migrator->getMigrationFiles(array_merge($migrator->paths(), [database_path('migrations')]));
        $nested = glob($path . '/*.php');
        $this->assertCount(9, $nested);
        foreach ($nested as $file) {
            $name = basename($file, '.php');
            $this->assertArrayHasKey($name, $files);
            $this->assertSame(realpath($file), realpath($files[$name]));
            $this->assertFileDoesNotExist(database_path('migrations/' . basename($file)));
        }
    }

    public function test_sql_dependencies_and_namespaced_seeders_resolve(): void
    {
        foreach (glob(database_path('migrations/cwis/equity/*.php')) as $file) {
            preg_match_all("/database_path\('([^']+\\.sql)'\)/", file_get_contents($file), $matches);
            foreach ($matches[1] as $sql) { $this->assertFileExists(database_path($sql)); }
        }
        $this->assertTrue(class_exists(\Database\Seeders\Cwis\Equity\CwisDataSourceSeeder::class));
        $this->assertTrue(class_exists(\Database\Seeders\Cwis\Equity\CwisFormFieldsSeeder::class));
    }
}
