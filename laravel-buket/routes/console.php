<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

Artisan::command('generate:mermaid', function () {
    $this->line("classDiagram");
    
    // 1. GENERATE CONTROLLERS DENGAN METHOD NYATA (REFLECTION)
    $controllerPath = app_path('Http/Controllers');
    if (File::exists($controllerPath)) {
        foreach (File::allFiles($controllerPath) as $file) {
            $className = $file->getFilenameWithoutExtension();
            
            // Lewati controller bawaan base Laravel
            if (in_array($className, ['Controller'])) {
                continue;
            }

            // Dapatkan namespace lengkap secara dinamis
            $relativePath = str_replace([app_path(), '.php', '/'], ['', '', '\\'], $file->getRealPath());
            $fullClassName = 'App' . $relativePath;

            if (class_exists($fullClassName)) {
                $this->line("    class $className {");
                
                // Gunakan Reflection untuk membaca fungsi di dalam Controller
                $reflection = new \ReflectionClass($fullClassName);
                $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
                
                foreach ($methods as $method) {
                    // Hanya ambil fungsi buatan Anda sendiri, bukan bawaan Laravel Controller
                    if ($method->class === $fullClassName && !Str::startsWith($method->name, '__')) {
                        
                        // Baca parameter fungsi jika ada
                        $params = [];
                        foreach ($method->getParameters() as $param) {
                            $paramType = $param->hasType() ? $param->getType()->getName() : '';
                            $params[] = ($paramType ? $paramType . ' ' : '') . '$' . $param->name;
                        }
                        $paramString = implode(', ', $params);
                        
                        // Cetak nama method asli ke diagram
                        $this->line("        +$method->name($paramString)");
                    }
                }
                $this->line("    }");
            }
        }
    }

    // 2. GENERATE MODELS DENGAN ATRIBUT NYATA DATABASE
    $tables = DB::select('SHOW TABLES');
    $dbName = DB::getDatabaseName();
    $keyName = "Tables_in_" . $dbName;

    foreach ($tables as $table) {
        $tableName = $table->$keyName;
        if (in_array($tableName, ['migrations', 'failed_jobs', 'personal_access_tokens', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'])) {
            continue;
        }

        $modelName = Str::studly(Str::singular($tableName));
        $this->line("    class $modelName {");

        $columns = Schema::getColumnListing($tableName);
        foreach ($columns as $column) {
            $type = Schema::getColumnType($tableName, $column);
            $formattedType = match($type) {
                'bigint' => 'BigInt',
                'integer', 'int' => 'Int',
                'string', 'varchar' => 'String',
                'text' => 'Text',
                'decimal' => 'Decimal',
                'datetime', 'timestamp' => 'Timestamp',
                default => ucfirst($type)
            };
            $this->line("        -$formattedType $column");
        }
        $this->line("    }");
    }
})->purpose('Membaca method asli controller dan atribut asli database');
