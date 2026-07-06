/**
 * ManageCustomers.js
 * Handles UI interactions, Modals, AJAX data fetching, 
 * Image Cropping, and Chart rendering for the Customer Management page.
 */

/* ── Global Variables ── */
let currentChart = null;
let currentChartType = 'users';
let custCropperInstance = null;

/* ── Filter Panel Logic ── */
function toggleFilter() {
    const filterPanel = document.getElementById('filterPanel');
    if (filterPanel) {
        filterPanel.classList.toggle('open');
    }
}

/* ── Details Modal (AJAX) ── */
function openDetailsModal(userId) {
    const modalBody = document.getElementById('detailsModalBody');
    const modalOverlay = document.getElementById('detailsModal');
    
    modalBody.innerHTML = '<p style="color:#94a3b8;text-align:center;padding:40px 0;">Loading...</p>';
    modalOverlay.classList.add('active');

    fetch('ManageCustomers.php?fetch_details=' + userId)
        .then(response => response.json())
        .then(data => {
            const statusColor = {
                'Confirmed' : '#10b981',
                'Pending'   : '#f59e0b',
                'Cancelled' : '#ef4444',
                'Completed' : '#6366f1'
            };

            let bookingsHtml = data.recent.length
                ? `<div style="max-height:180px;overflow-y:auto;padding-right:4px;margin-bottom:16px;">` +
                  data.recent.map(b => `
                    <a href="../Bookings_Management/ManageBookings.php?highlight=${b.id}"
                       style="text-decoration:none;color:inherit;display:block;">
                        <div class="detail-booking-row" style="cursor:pointer;">
                            <span>${b.court_name}</span>
                            <span>${b.booking_date} &nbsp;${b.start_time}–${b.end_time}</span>
                            <span class="wallet-badge"
                                  style="background:${statusColor[b.status]}20;color:${statusColor[b.status]}">
                                ${b.status}
                            </span>
                        </div>
                    </a>`).join('') +
                  `</div>`
                : '<p style="color:#94a3b8;font-size:13px;">No bookings yet.</p>';

            // Customer side stores the full relative path, admin side stores filename only — handle both
            const avatarSrc = data.user.profile_picture
                ? (data.user.profile_picture.includes('/')
                    ? '../../' + data.user.profile_picture
                    : '../../Pictures/Admin_Module/users/' + data.user.profile_picture)
                : '../../Pictures/Admin_Module/users/default_avatar.png';

            modalBody.innerHTML = `
                <div class="detail-profile">
                    <img src="${avatarSrc}"
                        style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:3px solid #f59e0b;">
                    <div>
                        <div style="font-size:18px;font-weight:800;color:#0f172a;">${data.user.name}</div>
                        <div style="font-size:13px;color:#94a3b8;">${data.user.email}</div>
                    </div>
                </div>
                <div class="detail-grid">
                    <div class="detail-field"><label>Phone</label><span>${data.user.phone || '—'}</span></div>
                    <div class="detail-field"><label>Gender</label><span>${data.user.gender || '—'}</span></div>
                    <div class="detail-field"><label>Joined</label><span>${data.user.created_at}</span></div>
                    <div class="detail-field"><label>Wallet</label><span>RM ${parseFloat(data.user.wallet_balance).toFixed(2)}</span></div>
                    <div class="detail-field"><label>Points</label><span>${data.user.loyalty_points ?? 0} pts</span></div>
                    <div class="detail-field"><label>Total Bookings</label><span>${data.stats.total_bookings}</span></div>
                    <div class="detail-field"><label>Total Spent</label><span>RM ${parseFloat(data.stats.total_spent || 0).toFixed(2)}</span></div>
                </div>
                <div style="margin-top:20px;">
                    <div style="font-size:11px;font-weight:700;color:#94a3b8;margin-bottom:10px;text-transform:uppercase;letter-spacing:0.5px;">
                        Recent Bookings
                    </div>
                    ${bookingsHtml}
                </div>`;
        })
        .catch(err => {
            modalBody.innerHTML = '<p style="color:#ef4444;text-align:center;">Error loading details.</p>';
            console.error(err);
        });
}

function closeDetailsModal() {
    document.getElementById('detailsModal').classList.remove('active');
}

/* ── Edit Customer Modal ── */
function openCustomerEditModal(id, name, phone, gender, wallet, points, img) {
    document.getElementById('cust-modal-id').value     = id;
    document.getElementById('cust-modal-name').value   = name;
    document.getElementById('cust-modal-phone').value  = phone;
    document.getElementById('cust-modal-wallet').value = wallet;
    document.getElementById('cust-modal-points').value = points;
    document.getElementById('cust-modal-gender').value = gender;
    document.getElementById('cust-cropped-img-data').value = '';

    const preview = document.getElementById('cust-modal-img-preview');
    preview.src = img
        ? (img.includes('/') ? '../../' + img : '../../Pictures/Admin_Module/users/' + img)
        : '../../Pictures/Admin_Module/users/default_avatar.png';

    document.getElementById('custEditPanel').style.display = 'block';
    document.getElementById('custCropPanel').style.display = 'none';
    document.getElementById('customerEditModal').classList.add('active');
}

function closeCustomerEditModal() {
    if(custCropperInstance){
        custCropperInstance.destroy();
        custCropperInstance = null;
    }
    document.getElementById('customerEditModal').classList.remove('active');
}

/* ── Wallet Modal ── */
function openWalletModal(id, name, wallet) {
    document.getElementById('wallet-user-id').value            = id;
    document.getElementById('wallet-modal-name').textContent   = name;
    document.getElementById('wallet-modal-current').textContent= 'RM ' + parseFloat(wallet).toFixed(2);
    document.getElementById('walletModal').classList.add('active');
}

function closeWalletModal() {
    document.getElementById('walletModal').classList.remove('active');
}

/* ── Cropper logic ── */
function applyCustCrop() {
    if(!custCropperInstance) return;
    const canvas = custCropperInstance.getCroppedCanvas({ width: 300, height: 300 });
    const dataUrl = canvas.toDataURL('image/png');

    document.getElementById('cust-modal-img-preview').src  = dataUrl;
    document.getElementById('cust-cropped-img-data').value = dataUrl;

    custCropperInstance.destroy();
    custCropperInstance = null;

    document.getElementById('custCropPanel').style.display = 'none';
    document.getElementById('custEditPanel').style.display = 'block';
}

function cancelCustCrop() {
    if(custCropperInstance){
        custCropperInstance.destroy();
        custCropperInstance = null;
    }
    document.getElementById('cust-img-input').value = '';
    document.getElementById('custCropPanel').style.display = 'none';
    document.getElementById('custEditPanel').style.display = 'block';
}

/* ── Chart.js Logic ── */
function getFilteredDataset(type) {
    const d    = chartDatasets[type];
    const from = document.getElementById('chartFrom').value; 
    const to   = document.getElementById('chartTo').value;
    
    if (!from && !to) return { labels: d.allLabels, data: d.allData };
    
    const filtered = { labels: [], data: [] };
    const months = { Jan:0,Feb:1,Mar:2,Apr:3,May:4,Jun:5,Jul:6,Aug:7,Sep:8,Oct:9,Nov:10,Dec:11 };
    
    d.allLabels.forEach((label, i) => {
        const parts    = label.split(' ');
        const year     = parseInt(parts[1]);
        const month    = months[parts[0]];
        const firstDay = new Date(year, month, 1);
        const lastDay  = new Date(year, month + 1, 0);
        
        if (from && lastDay  < new Date(from)) return;
        if (to   && firstDay > new Date(to))   return;
        
        filtered.labels.push(label);
        filtered.data.push(d.allData[i]);
    });
    return filtered;
}

function applyChartFilter() {
    const from = document.getElementById('chartFrom').value;
    const to   = document.getElementById('chartTo').value;
    if (from && to && from > to) {
        Toast.show('From date cannot be later than To date.', 'pending');
        return;
    }
    switchChart(currentChartType);
}

function resetChartFilter() {
    document.getElementById('chartFrom').value = '';
    document.getElementById('chartTo').value   = '';
    switchChart(currentChartType);
}

function switchChart(type) {
    currentChartType = type;
    const d        = chartDatasets[type];
    const filtered = getFilteredDataset(type);
    
    document.getElementById('chartTitle').textContent    = d.label;
    document.getElementById('chartSubtitle').textContent = type === 'users'
        ? 'Monthly registrations over past 12 months'
        : 'Monthly revenue over past 12 months';
        
    document.getElementById('btnUsers').classList.toggle('active',   type === 'users');
    document.getElementById('btnRevenue').classList.toggle('active', type === 'revenue');
    
    if(currentChart) currentChart.destroy();
    
    const ctx = document.getElementById('customerChart').getContext('2d');
    currentChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: filtered.labels,
            datasets: [{
                label               : d.label,
                data                : filtered.data,
                borderColor         : d.color,
                backgroundColor     : d.fill,
                borderWidth         : 2.5,
                tension             : 0.4,
                fill                : true,
                pointBackgroundColor: d.color,
                pointBorderColor    : '#fff',
                pointBorderWidth    : 2,
                pointRadius         : 5,
                pointHoverRadius    : 7
            }]
        },
        options: {
            responsive          : true,
            maintainAspectRatio : false,
            plugins: {
                legend : { display: false },
                tooltip: {
                    backgroundColor : '#0f172a',
                    titleColor      : '#94a3b8',
                    bodyColor       : '#fff',
                    padding         : 12,
                    cornerRadius    : 10,
                    callbacks: {
                        label: ctx => ' ' + d.prefix + ctx.parsed.y.toLocaleString() + d.suffix
                    }
                }
            },
            scales: {
                x: { 
                    grid: { display: false }, 
                    ticks: { color: '#94a3b8', font: { family: 'Outfit', size: 12 } } 
                },
                y: { 
                    grid: { color: '#f1f5f9' }, 
                    ticks: { color: '#94a3b8', font: { family: 'Outfit', size: 12 } } 
                }
            }
        }
    });
}

/* ── Event Listeners ── */
document.addEventListener('DOMContentLoaded', function() {
    
    // Initial Chart
    if (typeof chartDatasets !== 'undefined') {
        switchChart('users');
    }

    // Photo upload listener
    const imgInput = document.getElementById('cust-img-input');
    if (imgInput) {
        imgInput.addEventListener('change', function() {
            const file = this.files[0];
            if(!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const cropImg = document.getElementById('cust-crop-img');
                cropImg.src   = e.target.result;

                document.getElementById('custEditPanel').style.display = 'none';
                document.getElementById('custCropPanel').style.display = 'block';

                if(custCropperInstance) custCropperInstance.destroy();
                custCropperInstance = new Cropper(cropImg, {
                    aspectRatio  : 1,
                    viewMode     : 1,
                    autoCropArea : 0.8,
                    dragMode     : 'none',
                    movable      : false,
                    zoomable     : false,
                    zoomOnWheel  : false,
                    toggleDragModeOnDblclick: false
                });
            };
            reader.readAsDataURL(file);
        });
    }

    // Modal overlay click handlers
    const detailsModal = document.getElementById('detailsModal');
    if (detailsModal) {
        detailsModal.addEventListener('click', function(e) {
            if(e.target === this) closeDetailsModal();
        });
    }

    const editModal = document.getElementById('customerEditModal');
    if (editModal) {
        editModal.addEventListener('click', function(e) {
            if(e.target === this) closeCustomerEditModal();
        });
    }

    const walletModal = document.getElementById('walletModal');
    if (walletModal) {
        walletModal.addEventListener('click', function(e) {
            if(e.target === this) closeWalletModal();
        });
    }
});

document.querySelectorAll('.page-jump-input').forEach(function (input) {
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            input.closest('form').submit();
        }
    });
});