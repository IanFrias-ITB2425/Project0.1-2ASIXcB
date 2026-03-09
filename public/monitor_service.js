/**
 * EXTAGRAM REAL-TIME MONITORING SERVICE
 */
document.addEventListener("DOMContentLoaded", function() {
    console.log("Servicio de monitorización iniciado...");

    function refreshTelemetry() {
        // Buscamos el endpoint del AuditEngine
        fetch('AuditEngine.php?action=telemetry')
            .then(response => response.json())
            .then(data => {
                // 1. Actualizar CPU
                const cpuText = document.querySelector('#cpu-usage-text');
                const cpuBar = document.querySelector('#cpu-bar');
                if (cpuText) cpuText.innerText = data.cpu + '%';
                if (cpuBar) cpuBar.style.width = data.cpu + '%';

                // 2. Actualizar RAM
                const ramText = document.querySelector('#ram-usage-text');
                const ramBar = document.querySelector('#ram-bar');
                if (ramText) ramText.innerText = data.ram + '%';
                if (ramBar) ramBar.style.width = data.ram + '%';

                // 3. Actualizar Uptime y CWD
                const uptimeField = document.querySelector('#uptime-display');
                if (uptimeField) uptimeField.innerText = data.uptime;
                
                // 4. Cambiar color de barras si hay peligro (>80%)
                if (data.cpu > 80) {
                    if (cpuBar) cpuBar.style.backgroundColor = '#ef4444';
                } else {
                    if (cpuBar) cpuBar.style.backgroundColor = '#3b82f6';
                }
            })
            .catch(err => console.error("Error obteniendo telemetría:", err));
    }

    // Ejecutar cada 3 segundos
    setInterval(refreshTelemetry, 3000);
    
    // Primera ejecución inmediata
    refreshTelemetry();
});
