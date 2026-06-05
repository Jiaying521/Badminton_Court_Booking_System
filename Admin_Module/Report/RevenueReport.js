new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: chartLabels,
        datasets: [{
            label: 'Daily Revenue (RM)',
            data: chartValues,
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245,158,11,0.08)',
            borderWidth: 2,
            pointBackgroundColor: '#f59e0b',
            pointRadius: 4,
            tension: 0.3,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: val => 'RM ' + val.toFixed(2)
                }
            }
        }
    }
});

function openExportModal() {
    document.getElementById('exportModal').style.display = 'flex';
}

function closeExportModal() {
    document.getElementById('exportModal').style.display = 'none';
}

function toggleCustomDate(show) {
    document.getElementById('customDateBox').style.display = show ? 'block' : 'none';
}

function runExport(mode) {
    const scope = document.querySelector('input[name="exportScope"]:checked').value;
    let startDate, endDate;

    if (scope === 'current') {
        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const lastDay = new Date(y, now.getMonth() + 1, 0).getDate();
        startDate = `${y}-${m}-01`;
        endDate   = `${y}-${m}-${lastDay}`;
    } else {
        startDate = document.getElementById('exportStart').value;
        endDate   = document.getElementById('exportEnd').value;
        if (!startDate || !endDate) { Toast.show('Please select both dates.', 'pending'); return; }
    }

    const url = `export_report.php?start_date=${startDate}&end_date=${endDate}&mode=${mode}`;

    if (mode === 'print') {
        // Open PDF in new tab — user can press Ctrl+P
        window.open(url, '_blank');
    } else {
        // Trigger download
        window.location.href = url;
    }

    closeExportModal();
}

// Close modal when clicking outside
window.addEventListener('click', e => {
    const modal = document.getElementById('exportModal');
    if (e.target === modal) closeExportModal();
});