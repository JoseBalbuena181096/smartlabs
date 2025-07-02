<?php
echo "<h1>✅ PHP funciona correctamente</h1>";
echo "<p>Ruta base: " . dirname(__DIR__) . "</p>";
echo "<p>Hora actual: " . date('Y-m-d H:i:s') . "</p>";
echo "<h2>🔗 Enlaces de prueba:</h2>";
echo '<a href="/public/Auth/login">Login</a><br>';
echo '<a href="/public/Dashboard">Dashboard</a><br>';
echo '<a href="/public/Device">Dispositivos</a><br>';
echo '<a href="/public/Loan">Préstamos</a><br>';
?> 