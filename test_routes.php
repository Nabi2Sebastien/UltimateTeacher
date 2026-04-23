<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

// Test simple route
try {
    $routes = app('router')->getRoutes();
    
    echo "Routes trouvées:\n";
    echo "================\n";
    
    foreach ($routes as $route) {
        if (strpos($route->uri(), 'referentiel-models') !== false) {
            echo $route->methods()[0] . ' ' . $route->uri() . ' -> ' . $route->getAction('uses') . "\n";
        }
    }
    
    echo "\nTest de la route debug:\n";
    echo "=====================\n";
    
    $request = Illuminate\Http\Request::create('/referentiel-models/debug', 'GET');
    
    try {
        $response = app()->handle($request);
        echo "Status: " . $response->getStatusCode() . "\n";
        
        if ($response->getStatusCode() === 404) {
            echo "Route non trouvée - vérifiez le fichier de routes\n";
        } else {
            echo "Route trouvée!\n";
        }
    } catch (Exception $e) {
        echo "Erreur: " . $e->getMessage() . "\n";
        echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
} catch (Exception $e) {
    echo "Erreur générale: " . $e->getMessage() . "\n";
}
