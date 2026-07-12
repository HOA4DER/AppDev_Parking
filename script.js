document.addEventListener('submit', async function (e) {
    if (e.target && e.target.classList.contains('form-container')) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = form.querySelector('input[type="submit"]');
        if (!submitBtn) return;

        const originalBtnValue = submitBtn.value;

        let errorMsg = form.querySelector('.auth-error-msg');
        if (!errorMsg) {
            errorMsg = document.createElement('div');
            errorMsg.className = 'auth-error-msg';
            form.insertBefore(errorMsg, form.firstChild);
        }
        errorMsg.style.display = 'none';
        errorMsg.textContent = '';

        const formData = new FormData(form);
        const isSignUp = submitBtn.name === 'sign_up';
        formData.append('action', isSignUp ? 'sign_up' : 'sign_in');

        submitBtn.disabled = true;
        submitBtn.value = isSignUp ? 'Signing Up...' : 'Signing In...';

        try {
            const response = await fetch('main.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                errorMsg.style.color = '#00d4a8';
                errorMsg.style.borderColor = 'rgba(0, 212, 168, 0.2)';
                errorMsg.style.background = 'rgba(0, 212, 168, 0.06)';
                errorMsg.textContent = result.message;
                errorMsg.style.display = 'block';
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1000);
            } else {
                errorMsg.style.color = '#ff4d4d';
                errorMsg.style.borderColor = 'rgba(255, 77, 77, 0.2)';
                errorMsg.style.background = 'rgba(255, 77, 77, 0.06)';
                errorMsg.textContent = result.message;
                errorMsg.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.value = originalBtnValue;
            }
        } catch (error) {
            console.error('Auth error:', error);
            errorMsg.style.color = '#ff4d4d';
            errorMsg.style.borderColor = 'rgba(255, 77, 77, 0.2)';
            errorMsg.style.background = 'rgba(255, 77, 77, 0.06)';
            errorMsg.textContent = 'An unexpected error occurred. Please try again.';
            errorMsg.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.value = originalBtnValue;
        }
    }
});

function createAcc() {
    let right_body = document.querySelector('.right-body-bg');
    right_body.innerHTML = `
        <div class="header-subheader-login-container">
            <span class="header-login-text">Create Account</span>
            <span class="subheader-login-text">Sign up to reserve and manage your parking</span>
        </div>

        <div class="login-container create-account-container">
            <form class="form-container create-account-form" method="post">
                <input type="hidden" name="action" value="sign_up">
                <div class="name-input-container">
                    <div class="input-group">
                        <span class="login-email-text">FIRST NAME</span>
                        <input class="email-input-bar" type="text" name="first_name" placeholder="First Name" required>
                    </div>

                    <div class="input-group">
                        <span class="login-email-text">LAST NAME</span>
                        <input class="email-input-bar" type="text" name="last_name" placeholder="Last Name" required>
                    </div>
                </div>

                <div class="email-pass-container">
                    <span class="login-email-text">PHONE NUMBER</span>
                    <input class="email-input-bar" type="tel" name="phone_number" maxlength="11" placeholder="09XXXXXXXXX" required>

                    <span class="login-email-text">PLATE NUMBER</span>
                    <input class="email-input-bar" type="text" name="plate_number" maxlength="15" placeholder="ABC 1234" required>

                    <label class="login-email-text" for="vehicle-category">VEHICLE TYPE</label>
                    <select class="email-input-bar vehicle-type-select" id="vehicle-category" name="vehicle_category" required>
                        <option value="" selected disabled>Select your vehicle type</option>
                        <option value="Motorcycle (below 150cc)">Motorcycle (below 150cc)</option>
                        <option value="Bigbike (Above 400cc)">Bigbike (Above 400cc)</option>
                        <option value="4wheels (Sedan)">4 wheels (Sedan)</option>
                        <option value="4wheels (SUV)">4 wheels (SUV)</option>
                        <option value="4wheels (Pickup)">4 wheels (Pickup)</option>
                        <option value="4wheels (Mid-size SUV)">4 wheels (Mid-size SUV)</option>
                    </select>

                    <span class="login-email-text">EMAIL</span>
                    <input class="email-input-bar" type="email" name="email" placeholder="you@example.com" required>

                    <span class="login-email-text">PASSWORD</span>
                    <input class="email-input-bar" type="password" name="password" placeholder="Create a password" required>
                </div>

                <div class="signIn-container">
                    <input class="signIn-btn" type="submit" name="sign_up" value="Sign Up">
                </div>
            </form>

            <div class="no-account-container account-switch-container">
                <span class="no-account-text">Already have an account?</span>
                <button class="create-acc-btn" type="button" onclick="signIn()">Sign In</button>
            </div>
        </div>
    `;
}

function signIn() {
    let right_body = document.querySelector('.right-body-bg');
    right_body.innerHTML = `
        <div class="header-subheader-login-container">
            <span class="header-login-text">Welcome Back</span>
            <span class="subheader-login-text">Sign in to manage your parking</span>
        </div>

        <div class="login-container">
            <form class="form-container" method="post">
                <input type="hidden" name="action" value="sign_in">
                <div class="email-pass-container">
                    <span class="login-email-text">EMAIL</span>
                    <input class="email-input-bar" type="email" name="email" placeholder="you@example.com" required>

                    <span class="login-email-text">PASSWORD</span>
                    <input class="email-input-bar" type="password" name="password" placeholder="••••••••••••••••••" required>
                </div>

                <div class="signIn-container">
                    <input class="signIn-btn" type="submit" name="sign_in" value="Sign In">
                </div>
            </form>

            <div class="no-account-container">
                <span class="no-account-text">No account yet?</span>
                <button class="create-acc-btn" type="button" onclick="createAcc()">Create One</button>
            </div>
        </div>
    `;
}

document.addEventListener('DOMContentLoaded', function () {
    const scrollButtons = document.querySelectorAll('[data-scroll-target]');
    const navButtons = document.querySelectorAll('.nav-bar-btn[data-scroll-target]');
    const animatedSections = document.querySelectorAll(
        '.promotion-body-container, .how-it-works-section, .offer-section, .pricing-section, .main-footer'
    );
    const navTargets = Array.from(navButtons)
        .map(function (button) {
            return document.getElementById(button.getAttribute('data-scroll-target'));
        })
        .filter(Boolean);
    let scrollAnimationFrame = null;
    const visibleSections = new WeakSet();

    function setActiveNavButton(targetId) {
        navButtons.forEach(function (button) {
            const isActiveButton = button.getAttribute('data-scroll-target') === targetId;
            button.classList.toggle('active-nav-btn', isActiveButton);
        });
    }

    scrollButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();

            const targetId = button.getAttribute('data-scroll-target');
            const targetSection = document.getElementById(targetId);

            if (!targetSection) {
                return;
            }

            setActiveNavButton(targetId);

            targetSection.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });

            targetSection.focus({
                preventScroll: true
            });
        });
    });

    function updateActiveNavOnScroll() {
        let activeTargetId = '';

        navTargets.forEach(function (section) {
            const sectionPosition = section.getBoundingClientRect();
            const isCurrentSection = sectionPosition.top <= window.innerHeight * 0.35 &&
                sectionPosition.bottom >= window.innerHeight * 0.2;

            if (isCurrentSection) {
                activeTargetId = section.id;
            }
        });

        if (activeTargetId) {
            setActiveNavButton(activeTargetId);
        }
    }

    function triggerScrollAnimation() {
        animatedSections.forEach(function (section) {
            const sectionPosition = section.getBoundingClientRect();
            const isVisible = sectionPosition.top < window.innerHeight * 0.85 &&
                sectionPosition.bottom > window.innerHeight * 0.15;

            if (!isVisible) {
                visibleSections.delete(section);
                return;
            }

            if (visibleSections.has(section)) {
                return;
            }

            section.classList.remove('scroll-up-animate');
            void section.offsetWidth;
            section.classList.add('scroll-up-animate');
            visibleSections.add(section);
        });
    }

    window.addEventListener('scroll', function () {
        if (scrollAnimationFrame) {
            return;
        }

        scrollAnimationFrame = window.requestAnimationFrame(function () {
            triggerScrollAnimation();
            updateActiveNavOnScroll();
            scrollAnimationFrame = null;
        });
    });

    triggerScrollAnimation();
    updateActiveNavOnScroll();
});

document.addEventListener('DOMContentLoaded', function () {
    const bookingPage = document.querySelector('.booking-page-section');

    if (!bookingPage) {
        return;
    }

    const locations = [
        {
            id: 'sm-moa',
            name: 'SM Mall of Asia Parking',
            distance: 'Pasay City',
            price: 'PHP 60-100/hr',
            rate: 80,
            spots: 58,
            rating: '4.8 verified',
            floors: ['Basement 2', 'Basement 1', 'Ground Floor', 'Level 2', 'Level 3']
        },
        {
            id: 'sm-megamall',
            name: 'SM Megamall Parking',
            distance: 'Mandaluyong City',
            price: 'PHP 70-110/hr',
            rate: 90,
            spots: 46,
            rating: '4.9 verified',
            floors: ['Basement 2', 'Basement 1', 'Ground Floor', 'Level 2', 'Level 3']
        },
        {
            id: 'ayala-manila-bay',
            name: 'Ayala Malls Manila Bay Parking',
            distance: 'Paranaque City',
            price: 'PHP 60-90/hr',
            rate: 75,
            spots: 39,
            rating: '4.7 verified',
            floors: ['Basement 2', 'Basement 1', 'Ground Floor', 'Level 2']
        },
        {
            id: 'glorietta',
            name: 'Glorietta Parking',
            distance: 'Makati City',
            price: 'PHP 80-120/hr',
            rate: 100,
            spots: 34,
            rating: '4.6 verified',
            floors: ['Basement 2', 'Basement 1', 'Ground Floor', 'Level 2']
        },
        {
            id: 'trinoma',
            name: 'TriNoma Parking',
            distance: 'Quezon City',
            price: 'PHP 50-90/hr',
            rate: 70,
            spots: 41,
            rating: '4.8 verified',
            floors: ['Basement 2', 'Basement 1', 'Ground Floor', 'Level 2']
        }
    ];

    const spotTemplates = [
        { label: 'A01', type: 'standard', status: 'available' },
        { label: 'A02', type: 'standard', status: 'available' },
        { label: 'A03', type: 'standard', status: 'occupied' },
        { label: 'A04', type: 'standard', status: 'available' },
        { label: 'A05', type: 'standard', status: 'reserved' },
        { label: 'A06', type: 'standard', status: 'available' },
        { label: 'B01', type: 'standard', status: 'available' },
        { label: 'B02', type: 'standard', status: 'occupied' },
        { label: 'B03', type: 'standard', status: 'available' },
        { label: 'EV1', type: 'ev', status: 'available' },
        { label: 'EV2', type: 'ev', status: 'reserved' },
        { label: 'PWD1', type: 'accessible', status: 'available' },
        { label: 'PWD2', type: 'accessible', status: 'occupied' },
        { label: 'M01', type: 'motorcycle', status: 'available' },
        { label: 'M02', type: 'motorcycle', status: 'available' },
        { label: 'B04', type: 'standard', status: 'available' },
        { label: 'B05', type: 'standard', status: 'reserved' },
        { label: 'B06', type: 'standard', status: 'available' }
    ];

    const locationList = document.querySelector('.location-list');
    const floorTabs = document.querySelector('.floor-tabs');
    const parkingGrid = document.querySelector('.parking-grid');
    const selectedLocationName = document.querySelector('.selected-location-name');
    const selectedLocationDistance = document.querySelector('.selected-location-distance');
    const selectedLocationMeta = document.querySelector('.selected-location-meta');
    const selectedFloorName = document.querySelector('.selected-floor-name');
    const confirmButton = document.querySelector('.confirm-booking-btn');
    const confirmationText = document.querySelector('.booking-confirmation');
    const receiptEndpoint = document.querySelector('[data-receipt-endpoint]');
    const receiptPanel = document.querySelector('[data-receipt-panel]');
    const downloadReceiptButton = document.querySelector('[data-download-receipt]');
    const receiptFields = {
        number: document.querySelector('[data-receipt-field="number"]'),
        issued: document.querySelector('[data-receipt-field="issued"]'),
        location: document.querySelector('[data-receipt-field="location"]'),
        floor: document.querySelector('[data-receipt-field="floor"]'),
        spot: document.querySelector('[data-receipt-field="spot"]'),
        arrival: document.querySelector('[data-receipt-field="arrival"]'),
        duration: document.querySelector('[data-receipt-field="duration"]'),
        plate: document.querySelector('[data-receipt-field="plate"]'),
        vehicleCategory: document.querySelector('[data-receipt-field="vehicle-category"]'),
        overnight: document.querySelector('[data-receipt-field="overnight"]'),
        payment: document.querySelector('[data-receipt-field="payment"]'),
        total: document.querySelector('[data-receipt-field="total"]')
    };
    const receiptQrImg = document.getElementById('receipt-qr-img');
    const durationSelect = document.querySelector('[data-duration]');
    const arrivalInput = document.querySelector('[data-arrival-time]');
    const overnightCheckbox = document.getElementById('overnight-chk');
    const summaryFields = {
        location: document.querySelector('[data-summary="location"]'),
        floor: document.querySelector('[data-summary="floor"]'),
        spot: document.querySelector('[data-summary="spot"]'),
        rate: document.querySelector('[data-summary="rate"]'),
        total: document.querySelector('[data-summary="total"]')
    };

    let selectedLocation = null;
    let selectedFloor = '';
    let selectedSpot = null;
    let currentStep = 1;
    let highestStep = 1;
    let activeReceipt = null;

    function formatPaymentMethod(method) {
        const labels = {
            gcash: 'GCash',
            maya: 'Maya',
            paypal: 'PayPal',
            card: 'Debit/Credit Card',
            cash: 'Cash'
        };

        return labels[String(method || '').toLowerCase()] || method || 'GCash';
    }

    let occupiedSpotsFromDb = [];

    const checkoutModal = document.getElementById('checkout-modal');
    const closeCheckoutBtn = document.getElementById('close-checkout-btn');
    const cancelCheckoutBtn = document.getElementById('cancel-checkout-btn');
    const paySubmitBtn = document.getElementById('pay-submit-btn');
    const paymentRadioButtons = document.querySelectorAll('input[name="payment_method_sel"]');
    const checkoutQrImg = document.getElementById('checkout-qr-img');

    async function fetchOccupiedSpots() {
        if (!selectedLocation || !selectedFloor) return;
        const arrivalTime = arrivalInput.value;
        const duration = durationSelect.value;

        try {
            const response = await fetch(`book.php?action=get_occupied_spots&location_id=${selectedLocation.id}&floor=${encodeURIComponent(selectedFloor)}&arrival_time=${arrivalTime}&duration=${duration}`);
            if (response.ok) {
                const data = await response.json();
                occupiedSpotsFromDb = data.occupied_spots || [];
            }
        } catch (e) {
            console.error("Error fetching occupied spots:", e);
            occupiedSpotsFromDb = [];
        }
    }

    const bookingHistoryList = document.getElementById('booking-history-list');

    async function refreshBookingHistory() {
        if (!bookingHistoryList) return;

        try {
            const response = await fetch('get_bookings.php');
            if (response.ok) {
                const bookings = await response.json();
                renderBookingHistory(bookings);
            } else {
                bookingHistoryList.innerHTML = '<div style="color: #8d8b8b; text-align: center; width: 100%; padding: 20px;">Failed to load booking history.</div>';
            }
        } catch (e) {
            console.error("Error fetching bookings:", e);
            bookingHistoryList.innerHTML = '<div style="color: #8d8b8b; text-align: center; width: 100%; padding: 20px;">Error loading booking history.</div>';
        }
    }

    function renderBookingHistory(bookings) {
        if (!bookingHistoryList) return;

        if (!bookings || bookings.length === 0) {
            bookingHistoryList.innerHTML = `
                <div style="grid-column: 1/-1; border: 1px dashed rgba(255,255,255,0.1); border-radius: 12px; padding: 40px; text-align: center; color: #8d8b8b;">
                    <img src="images/location.svg" style="width: 24px; opacity: 0.3; margin-bottom: 12px;" alt="">
                    <p style="margin: 0; font-size: 15px;">No bookings found. Start by choosing a location above!</p>
                </div>
            `;
            return;
        }

        bookingHistoryList.innerHTML = bookings.map(function (b) {
            const isConfirmed = b.status === 'confirmed';
            const statusClass = isConfirmed ? 'status-confirmed' : 'status-cancelled';

            const timeParts = b.arrival_time.split(':');
            let hours = Number(timeParts[0]);
            const minutes = timeParts[1] || '00';
            const period = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            const formattedTime = hours + ':' + minutes + ' ' + period;

            const cancelBtn = isConfirmed
                ? `<button class="cancel-booking-btn" data-cancel-id="${b.id}">Cancel Reservation</button>`
                : '';

            return `
                <div class="history-card">
                    <div class="history-card-header">
                        <span class="history-receipt-no">${b.receipt_number}</span>
                        <span class="history-status-badge ${statusClass}">${b.status}</span>
                    </div>
                    <div class="history-card-body">
                        <div>
                            <span>Location</span>
                            <strong>${b.location_name}</strong>
                        </div>
                        <div>
                            <span>Floor</span>
                            <strong>${b.floor}</strong>
                        </div>
                        <div>
                            <span>Spot</span>
                            <strong>${b.spot_label} (${getSpotTypeLabel(b.spot_type)})</strong>
                        </div>
                        <div>
                            <span>Arrival</span>
                            <strong>${formattedTime}</strong>
                        </div>
                        <div>
                            <span>Duration</span>
                            <strong>${b.duration_hours} ${b.duration_hours > 1 ? 'hours' : 'hour'}</strong>
                        </div>
                        <div>
                            <span>Vehicle</span>
                            <strong>${b.plate_number || 'Default'}${b.vehicle_category ? ' (' + b.vehicle_category + ')' : ''}</strong>
                        </div>
                        <div>
                            <span>Payment Method</span>
                            <strong>${formatPaymentMethod(b.payment_method)}</strong>
                        </div>
                        <div>
                            <span>Total Paid</span>
                            <strong>PHP ${Number(b.total_amount).toLocaleString('en-US')}</strong>
                        </div>
                    </div>
                    ${cancelBtn}
                </div>
            `;
        }).join('');
    }

    function formatCurrency(amount) {
        return 'PHP ' + amount.toLocaleString('en-US');
    }

    function formatArrivalTime(timeValue) {
        const timeParts = timeValue.split(':');
        let hours = Number(timeParts[0]);
        const minutes = timeParts[1] || '00';
        const period = hours >= 12 ? 'PM' : 'AM';

        hours = hours % 12 || 12;

        return hours + ':' + minutes + ' ' + period;
    }

    function getDurationLabel() {
        return durationSelect.options[durationSelect.selectedIndex].text;
    }

    function getSpotTypeLabel(type) {
        const labels = {
            ev: 'EV Charging',
            accessible: 'Accessible',
            motorcycle: 'Motorcycle',
            standard: 'Standard'
        };

        return labels[type] || 'Standard';
    }

    function getBookingTotal() {
        if (!selectedLocation) return 0;

        const durationHours = Number(durationSelect.value);
        const isOvernight = overnightCheckbox && overnightCheckbox.checked;

        if (isOvernight) {
            const timeParts = arrivalInput.value.split(':');
            const hours = parseInt(timeParts[0] || '0', 10);
            const isPM = hours >= 12;
            const basePrice = isPM ? 120 : 145;

            const arrivalDate = new Date();
            arrivalDate.setHours(hours, parseInt(timeParts[1] || '0', 10), 0, 0);

            const departureDate = new Date(arrivalDate.getTime() + durationHours * 3600 * 1000);

            const next7am = new Date(arrivalDate.getTime());
            next7am.setDate(next7am.getDate() + 1);
            next7am.setHours(7, 0, 0, 0);

            let extraCost = 0;
            if (departureDate > next7am) {
                const diffMs = departureDate - next7am;
                const extraHours = Math.ceil(diffMs / (3600 * 1000));
                extraCost = extraHours * 20;
            }

            return basePrice + extraCost;
        } else {
            if (durationHours <= 4) {
                return 50;
            } else {
                return 50 + (durationHours - 4) * 15;
            }
        }
    }

    function hideReceipt() {
        activeReceipt = null;

        if (!receiptPanel) {
            return;
        }

        receiptPanel.hidden = true;
        receiptPanel.classList.remove('is-visible');
    }

    function resetConfirmation() {
        confirmationText.textContent = '';
        hideReceipt();
    }

    async function requestReceiptDetails(paymentMethod) {
        const endpoint = receiptEndpoint ? receiptEndpoint.getAttribute('data-receipt-endpoint') : 'book.php';

        const vehicleSelect = document.getElementById('booking-vehicle');
        let chosenPlate = '';
        let chosenVehicleCategory = '';
        if (vehicleSelect) {
            chosenPlate = vehicleSelect.value;
            chosenVehicleCategory = vehicleSelect.options[vehicleSelect.selectedIndex]?.dataset.category || '';
        }

        const params = new URLSearchParams();
        params.append('action', 'confirm_booking');
        params.append('location_id', selectedLocation.id);
        params.append('location_name', selectedLocation.name);
        params.append('floor', selectedFloor);
        params.append('spot_label', selectedSpot.label);
        params.append('spot_type', selectedSpot.type);
        params.append('arrival_time', arrivalInput.value);
        params.append('duration', durationSelect.value);
        params.append('hourly_rate', selectedLocation.rate);
        params.append('payment_method', paymentMethod);
        params.append('total_amount', getBookingTotal());
        params.append('is_overnight', overnightCheckbox && overnightCheckbox.checked ? 'true' : 'false');
        params.append('plate_number', chosenPlate);
        params.append('vehicle_category', chosenVehicleCategory);

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: params.toString()
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(errorText || 'Booking request failed');
        }

        const receipt = await response.json();

        if (receipt.error) {
            throw new Error(receipt.error);
        }

        if (!receipt.receipt_number || !receipt.issued_at) {
            throw new Error('Receipt response is incomplete');
        }

        return receipt;
    }

    function renderReceipt(receiptDetails) {
        const total = getBookingTotal();
        const isOvernight = overnightCheckbox && overnightCheckbox.checked;

        activeReceipt = {
            number: receiptDetails.receipt_number,
            issued: receiptDetails.issued_at,
            location: selectedLocation.name,
            area: selectedLocation.distance,
            floor: selectedFloor,
            spot: selectedSpot.label + ' (' + getSpotTypeLabel(selectedSpot.type) + ')',
            arrival: formatArrivalTime(arrivalInput.value),
            duration: getDurationLabel(),
            plate: receiptDetails.plate_number || '',
            vehicleCategory: receiptDetails.vehicle_category || '',
            overnight: isOvernight ? 'Yes' : 'No',
            rate: isOvernight ? (parseInt(arrivalInput.value.split(':')[0]) >= 12 ? 'PHP 120 (PM)' : 'PHP 145 (AM)') : 'PHP 50 (First 4h) + PHP 15/hr',
            payment: formatPaymentMethod(receiptDetails.payment_method),
            total: formatCurrency(total)
        };

        receiptFields.number.textContent = activeReceipt.number;
        receiptFields.issued.textContent = activeReceipt.issued;
        receiptFields.location.textContent = activeReceipt.location;
        receiptFields.floor.textContent = activeReceipt.floor;
        receiptFields.spot.textContent = activeReceipt.spot;
        receiptFields.arrival.textContent = activeReceipt.arrival;
        receiptFields.duration.textContent = activeReceipt.duration;
        if (receiptFields.plate) receiptFields.plate.textContent = activeReceipt.plate;
        if (receiptFields.vehicleCategory) receiptFields.vehicleCategory.textContent = activeReceipt.vehicleCategory || '--';
        if (receiptFields.overnight) receiptFields.overnight.textContent = activeReceipt.overnight;
        if (receiptFields.payment) receiptFields.payment.textContent = activeReceipt.payment;
        receiptFields.total.textContent = activeReceipt.total;

        if (receiptQrImg) {
            receiptQrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' + encodeURIComponent('SiksikParkingTicket_' + activeReceipt.number);
        }

        receiptPanel.hidden = false;
        receiptPanel.classList.remove('is-visible');
        void receiptPanel.offsetWidth;
        receiptPanel.classList.add('is-visible');
        receiptPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function buildReceiptDownloadText() {
        return [
            'Siksik Parking System Receipt',
            'Receipt No.: ' + activeReceipt.number,
            'Issued: ' + activeReceipt.issued,
            '',
            'Location: ' + activeReceipt.location,
            'Area: ' + activeReceipt.area,
            'Floor: ' + activeReceipt.floor,
            'Spot: ' + activeReceipt.spot,
            'Arrival: ' + activeReceipt.arrival,
            'Duration: ' + activeReceipt.duration,
            'Vehicle Plate: ' + activeReceipt.plate,
            'Vehicle Type: ' + activeReceipt.vehicleCategory,
            'Overnight Parking: ' + activeReceipt.overnight,
            'Rate Model: ' + activeReceipt.rate,
            'Payment Method: ' + activeReceipt.payment.toUpperCase(),
            'Total: ' + activeReceipt.total,
            '',
            'Thank you for booking with Siksik.'
        ].join('\n');
    }

    function setCurrentStep(step) {
        if (step > highestStep) {
            return;
        }

        currentStep = step;

        document.querySelectorAll('[data-step-nav]').forEach(function (button) {
            const buttonStep = Number(button.getAttribute('data-step-nav'));
            button.classList.toggle('is-active', buttonStep === currentStep);
            button.disabled = buttonStep > highestStep;
        });

        document.querySelectorAll('[data-booking-step]').forEach(function (panel) {
            panel.classList.toggle('is-current', Number(panel.getAttribute('data-booking-step')) === currentStep);
        });
    }

    function renderLocations() {
        locationList.innerHTML = locations.map(function (location) {
            const selectedClass = selectedLocation && selectedLocation.id === location.id ? ' is-selected' : '';

            return `
                <article class="location-card${selectedClass}" data-location-card="${location.id}">
                    <div class="location-card-top">
                        <h3>${location.name}</h3>
                        <span class="verified-badge">${location.rating}</span>
                    </div>
                    <div class="location-card-meta">
                        <span>${location.distance}</span>
                        <span>${location.price}</span>
                        <span>${location.spots} spots</span>
                    </div>
                    <button class="view-spots-btn" type="button" data-view-spots="${location.id}">View Floors</button>
                </article>
            `;
        }).join('');
    }

    function renderFloorTabs() {
        if (!selectedLocation) {
            floorTabs.innerHTML = '';
            return;
        }

        floorTabs.innerHTML = selectedLocation.floors.map(function (floor) {
            const activeClass = floor === selectedFloor ? ' is-active' : '';
            return `<button class="floor-tab${activeClass}" type="button" data-floor="${floor}">${floor}</button>`;
        }).join('');
    }

    function getFloorSpots() {
        const floorOffset = selectedLocation ? selectedLocation.floors.indexOf(selectedFloor) : 0;

        return spotTemplates.map(function (spot, index) {
            const clone = Object.assign({}, spot);

            if (occupiedSpotsFromDb.includes(clone.label)) {
                clone.status = 'reserved';
            } else {
                const shift = (index + floorOffset) % 9;

                if (clone.status === 'available' && shift === 0) {
                    clone.status = 'occupied';
                } else if (clone.status === 'available' && shift === 4) {
                    clone.status = 'reserved';
                }
            }

            return clone;
        });
    }

    function renderParkingGrid() {
        const spots = getFloorSpots();

        parkingGrid.innerHTML = spots.map(function (spot) {
            const selectedClass = selectedSpot && selectedSpot.label === spot.label ? ' is-selected' : '';
            const disabled = spot.status === 'occupied' || spot.status === 'reserved' ? ' disabled' : '';
            const typeClass = spot.type !== 'standard' ? ' is-' + spot.type : '';

            return `<button class="parking-spot is-${spot.status}${typeClass}${selectedClass}" type="button" data-spot="${spot.label}" data-spot-type="${spot.type}" data-spot-status="${spot.status}"${disabled}>${spot.label}</button>`;
        }).join('');
    }

    function updateSummary() {
        const total = getBookingTotal();
        const isOvernight = overnightCheckbox && overnightCheckbox.checked;

        summaryFields.location.textContent = selectedLocation ? selectedLocation.name : 'Not selected';
        summaryFields.floor.textContent = selectedFloor || 'Not selected';
        summaryFields.spot.textContent = selectedSpot ? selectedSpot.label : 'Not selected';

        if (selectedLocation) {
            if (isOvernight) {
                const timeParts = arrivalInput.value.split(':');
                const hours = parseInt(timeParts[0] || '0', 10);
                const isPM = hours >= 12;
                summaryFields.rate.textContent = isPM ? 'PHP 120 Base (PM)' : 'PHP 145 Base (AM)';
            } else {
                summaryFields.rate.textContent = 'PHP 50 Flat (1-4h) + PHP 15/hr';
            }
            summaryFields.total.textContent = formatCurrency(total);
        } else {
            summaryFields.rate.textContent = '--';
            summaryFields.total.textContent = '--';
        }
        confirmButton.disabled = !(selectedLocation && selectedFloor && selectedSpot) || Boolean(activeReceipt);
    }

    async function selectLocation(locationId) {
        selectedLocation = locations.find(function (location) {
            return location.id === locationId;
        });

        if (!selectedLocation) {
            return;
        }

        selectedFloor = selectedLocation.floors[0];
        selectedSpot = null;
        selectedLocationName.textContent = selectedLocation.name;
        selectedLocationDistance.textContent = selectedLocation.distance + ' • ' + selectedLocation.price;
        selectedLocationMeta.textContent = selectedLocation.spots + ' available spots • ' + selectedLocation.rating;
        selectedFloorName.textContent = selectedFloor;
        resetConfirmation();
        highestStep = Math.max(highestStep, 2);

        await fetchOccupiedSpots();

        renderLocations();
        renderFloorTabs();
        renderParkingGrid();
        updateSummary();
        setCurrentStep(2);
        document.querySelector('.booking-detail-panel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function selectFloor(floor) {
        selectedFloor = floor;
        selectedSpot = null;
        selectedFloorName.textContent = selectedFloor;
        resetConfirmation();
        highestStep = Math.max(highestStep, 3);

        await fetchOccupiedSpots();

        renderFloorTabs();
        renderParkingGrid();
        updateSummary();
        setCurrentStep(3);
        document.querySelector('.booking-spots-panel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function selectSpot(spotButton) {
        selectedSpot = {
            label: spotButton.getAttribute('data-spot'),
            type: spotButton.getAttribute('data-spot-type')
        };
        resetConfirmation();
        renderParkingGrid();
        updateSummary();
        setCurrentStep(3);
    }

    locationList.addEventListener('click', function (event) {
        const viewButton = event.target.closest('[data-view-spots]');
        const card = event.target.closest('[data-location-card]');

        if (viewButton) {
            selectLocation(viewButton.getAttribute('data-view-spots'));
            return;
        }

        if (card) {
            selectLocation(card.getAttribute('data-location-card'));
        }
    });

    floorTabs.addEventListener('click', function (event) {
        const floorButton = event.target.closest('[data-floor]');

        if (floorButton) {
            selectFloor(floorButton.getAttribute('data-floor'));
        }
    });

    parkingGrid.addEventListener('click', function (event) {
        const spotButton = event.target.closest('[data-spot]');

        if (!spotButton || spotButton.disabled) {
            return;
        }

        selectSpot(spotButton);
    });

    document.querySelectorAll('[data-step-nav]').forEach(function (button) {
        button.addEventListener('click', function () {
            const targetStep = Number(button.getAttribute('data-step-nav'));
            const targetPanel = document.querySelector('[data-booking-step="' + targetStep + '"]');

            setCurrentStep(targetStep);

            if (targetPanel) {
                targetPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    });

    [durationSelect, arrivalInput, overnightCheckbox].forEach(function (control) {
        if (!control) return;
        control.addEventListener('change', async function () {
            resetConfirmation();
            await fetchOccupiedSpots();
            renderParkingGrid();
            updateSummary();
        });
    });

    if (downloadReceiptButton) {
        downloadReceiptButton.addEventListener('click', function () {
            if (!activeReceipt) {
                return;
            }

            const receiptBlob = new Blob([buildReceiptDownloadText()], { type: 'text/plain' });
            const receiptUrl = URL.createObjectURL(receiptBlob);
            const downloadLink = document.createElement('a');

            downloadLink.href = receiptUrl;
            downloadLink.download = activeReceipt.number + '.txt';
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            downloadLink.remove();
            window.setTimeout(function () {
                URL.revokeObjectURL(receiptUrl);
            }, 0);
        });
    }

    function switchPaymentScreen(method) {
        const screens = {
            qr: document.getElementById('screen-qr'),
            paypal: document.getElementById('screen-paypal'),
            card: document.getElementById('screen-card'),
            cash: document.getElementById('screen-cash'),
            processing: document.getElementById('screen-processing')
        };

        Object.values(screens).forEach(s => { if (s) s.style.display = 'none'; });

        if (paySubmitBtn) {
            paySubmitBtn.disabled = false;
            paySubmitBtn.textContent = method === 'cash' ? 'Confirm Reservation' : 'Complete Payment';
        }

        if (method === 'gcash' || method === 'maya') {
            if (screens.qr) screens.qr.style.display = 'block';
            const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent('SiksikParking_' + method + '_' + getBookingTotal() + '_' + Date.now());
            if (checkoutQrImg) checkoutQrImg.src = qrUrl;
        } else if (method === 'paypal') {
            if (screens.paypal) screens.paypal.style.display = 'flex';
        } else if (method === 'card') {
            if (screens.card) screens.card.style.display = 'flex';
        } else if (method === 'cash') {
            if (screens.cash) screens.cash.style.display = 'flex';
        }
    }

    paymentRadioButtons.forEach(radio => {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.payment-option-label').forEach(lbl => {
                lbl.style.borderColor = 'rgba(255, 255, 255, 0.12)';
                lbl.style.background = 'rgba(8, 8, 8, 0.48)';
                lbl.style.color = '#8d8b8b';
            });

            const label = radio.closest('.payment-option-label');
            if (label) {
                label.style.borderColor = 'rgba(0, 212, 168, 0.3)';
                label.style.background = 'rgba(0, 212, 168, 0.05)';
                label.style.color = 'white';
            }

            switchPaymentScreen(radio.value);
        });
    });

    function closeCheckout() {
        if (checkoutModal) {
            checkoutModal.style.display = 'none';
        }
    }

    if (closeCheckoutBtn) closeCheckoutBtn.addEventListener('click', closeCheckout);
    if (cancelCheckoutBtn) cancelCheckoutBtn.addEventListener('click', closeCheckout);

    confirmButton.addEventListener('click', function () {
        if (!selectedLocation || !selectedFloor || !selectedSpot) {
            return;
        }

        const payLoc = document.getElementById('pay-location');
        const paySpot = document.getElementById('pay-spot');
        const payArr = document.getElementById('pay-arrival');
        const payDur = document.getElementById('pay-duration');
        const payTot = document.getElementById('pay-total');

        if (payLoc) payLoc.textContent = selectedLocation.name;
        if (paySpot) paySpot.textContent = selectedFloor + ' - ' + selectedSpot.label;
        if (payArr) payArr.textContent = formatArrivalTime(arrivalInput.value);
        if (payDur) payDur.textContent = getDurationLabel();
        if (payTot) payTot.textContent = formatCurrency(getBookingTotal());

        const gcashRadio = document.querySelector('input[name="payment_method_sel"][value="gcash"]');
        if (gcashRadio) {
            gcashRadio.checked = true;
            gcashRadio.dispatchEvent(new Event('change'));
        }

        if (checkoutModal) checkoutModal.style.display = 'flex';
        if (paySubmitBtn) {
            paySubmitBtn.disabled = false;
            paySubmitBtn.textContent = 'Complete Payment';
        }
    });

    if (paySubmitBtn) {
        paySubmitBtn.addEventListener('click', async function () {
            const selectedMethod = document.querySelector('input[name="payment_method_sel"]:checked').value;

            const qrScreen = document.getElementById('screen-qr');
            const paypalScreen = document.getElementById('screen-paypal');
            const cardScreen = document.getElementById('screen-card');
            const cashScreen = document.getElementById('screen-cash');
            const processingScreen = document.getElementById('screen-processing');
            const processingText = document.getElementById('payment-processing-text');

            if (qrScreen) qrScreen.style.display = 'none';
            if (paypalScreen) paypalScreen.style.display = 'none';
            if (cardScreen) cardScreen.style.display = 'none';
            if (cashScreen) cashScreen.style.display = 'none';
            if (processingScreen) processingScreen.style.display = 'block';
            if (processingText) processingText.textContent = selectedMethod === 'cash' ? 'Confirming Reservation...' : 'Processing Transaction...';

            paySubmitBtn.disabled = true;
            paySubmitBtn.textContent = selectedMethod === 'cash' ? 'Confirming...' : 'Verifying...';

            setTimeout(async function () {
                try {
                    const receiptDetails = await requestReceiptDetails(selectedMethod);

                    closeCheckout();

                    const successModal = document.getElementById('success-alert-modal');
                    if (successModal) {
                        const successTitle = document.getElementById('success-alert-title');
                        const refundCopy = document.getElementById('success-refund-copy');
                        if (successTitle) {
                            successTitle.textContent = selectedMethod === 'cash' ? 'Reservation Confirmed!' : 'Payment Successful!';
                        }
                        if (refundCopy) {
                            refundCopy.innerHTML = selectedMethod === 'cash'
                                ? 'Voided spots will be released to the public. No refund is needed for cash reservations until payment is collected on arrival.'
                                : 'Voided spots will be released to the public, and a <strong>50% refund</strong> will be issued to your payment method.';
                        }
                        successModal.style.display = 'flex';
                    }

                    const successCloseBtn = document.getElementById('success-alert-close-btn');
                    if (successCloseBtn) {
                        successCloseBtn.onclick = function () {
                            if (successModal) successModal.style.display = 'none';

                            confirmationText.style.color = '#00d4a8';
                            confirmationText.textContent = 'Booking confirmed for ' + selectedSpot.label + ' at ' + selectedLocation.name + ', ' + selectedFloor + '.';
                            renderReceipt(receiptDetails);
                        };
                    } else {
                        confirmationText.style.color = '#00d4a8';
                        confirmationText.textContent = 'Booking confirmed for ' + selectedSpot.label + ' at ' + selectedLocation.name + ', ' + selectedFloor + '.';
                        renderReceipt(receiptDetails);
                    }

                    await fetchOccupiedSpots();
                    renderParkingGrid();
                    await refreshBookingHistory();
                } catch (error) {
                    console.error('Booking failed:', error);
                    switchPaymentScreen(selectedMethod);
                    alert('Payment / Booking failed: ' + error.message);

                    paySubmitBtn.disabled = false;
                    paySubmitBtn.textContent = 'Complete Payment';
                }
            }, 1500);
        });
    }

    if (bookingHistoryList) {
        bookingHistoryList.addEventListener('click', async function (event) {
            const cancelBtn = event.target.closest('[data-cancel-id]');
            if (!cancelBtn) return;

            const bookingId = cancelBtn.getAttribute('data-cancel-id');
            if (!confirm('Are you sure you want to cancel this reservation?')) return;

            cancelBtn.disabled = true;
            cancelBtn.textContent = 'Cancelling...';

            try {
                const response = await fetch('book.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=cancel_booking&booking_id=${bookingId}`
                });

                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        await fetchOccupiedSpots();
                        renderParkingGrid();
                        await refreshBookingHistory();
                    } else {
                        alert('Cancellation failed: ' + result.error);
                        cancelBtn.disabled = false;
                        cancelBtn.textContent = 'Cancel Reservation';
                    }
                } else {
                    alert('Cancellation request failed.');
                    cancelBtn.disabled = false;
                    cancelBtn.textContent = 'Cancel Reservation';
                }
            } catch (e) {
                console.error("Error cancelling booking:", e);
                alert('An error occurred while cancelling the booking.');
                cancelBtn.disabled = false;
                cancelBtn.textContent = 'Cancel Reservation';
            }
        });

        refreshBookingHistory();
    }

    renderLocations();
    setCurrentStep(1);
    updateSummary();
});


document.addEventListener('DOMContentLoaded', function () {
    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax; Secure";
    }

    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    function injectCookieBanner() {
        if (document.getElementById('siksik-cookie-banner')) return;

        const bannerHtml = `
            <div id="siksik-cookie-banner" class="cookie-banner-container cookie-banner-hidden">
                <div class="cookie-banner-content">
                    <div class="cookie-banner-header">
                        <div class="cookie-title-group">
                            <svg class="cookie-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                                <path fill="#00d4a8" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm-1.5 5a1.5 1.5 0 1 1-1.5 1.5A1.5 1.5 0 0 1 10.5 7zm-3 5a1.5 1.5 0 1 1-1.5 1.5A1.5 1.5 0 0 1 7.5 12zm2.5 5a1.5 1.5 0 1 1-1.5 1.5A1.5 1.5 0 0 1 10 17zm5.5 0a1.5 1.5 0 1 1-1.5-1.5 1.5 1.5 0 0 1 1.5 1.5zm.5-4.5a1.5 1.5 0 1 1-1.5-1.5 1.5 1.5 0 0 1 1.5 1.5zm-1.5-4a1.5 1.5 0 1 1 1.5 1.5 1.5 0 0 1-1.5-1.5zm3.5 2.5a.5.5 0 1 1-.5-.5.5.5 0 0 1 .5.5z"/>
                            </svg>
                            <h3>Cookie Preferences</h3>
                        </div>
                        <button class="cookie-close-btn" id="siksik-cookie-close">&times;</button>
                    </div>
                    
                    <div class="cookie-banner-body" id="siksik-cookie-main-view">
                        <p>We use cookies to improve your experience, analyze site usage, and support our marketing efforts. By clicking "Accept All", you agree to our use of all cookies. You can customize your preferences by clicking "Customize" or reject all non-essential cookies by clicking "Reject All".</p>
                        <div class="cookie-banner-actions">
                            <button class="cookie-btn cookie-btn-secondary" id="siksik-cookie-reject">Reject All</button>
                            <button class="cookie-btn cookie-btn-link" id="siksik-cookie-customize-btn">Customize</button>
                            <button class="cookie-btn cookie-btn-primary" id="siksik-cookie-accept">Accept All</button>
                        </div>
                    </div>
                    
                    <div class="cookie-banner-body cookie-banner-hidden" id="siksik-cookie-settings-view">
                        <p class="settings-intro">Select which cookies you want to allow. Necessary cookies are required for the website to function.</p>
                        
                        <div class="cookie-option-list">
                            <div class="cookie-option-item">
                                <div class="cookie-option-info">
                                    <span class="cookie-option-title">Necessary Cookies <span class="badge-required">Required</span></span>
                                    <span class="cookie-option-desc">These cookies are essential for you to browse the website and use its features, such as secure booking and account login.</span>
                                </div>
                                <label class="cookie-switch">
                                    <input type="checkbox" checked disabled>
                                    <span class="cookie-slider"></span>
                                </label>
                            </div>
                            
                            <div class="cookie-option-item">
                                <div class="cookie-option-info">
                                    <span class="cookie-option-title">Preference Cookies</span>
                                    <span class="cookie-option-desc">These cookies allow us to remember choices you make (such as your preferred parking locations or UI settings).</span>
                                </div>
                                <label class="cookie-switch">
                                    <input type="checkbox" id="siksik-cookie-pref-chk">
                                    <span class="cookie-slider"></span>
                                </label>
                            </div>
                            
                            <div class="cookie-option-item">
                                <div class="cookie-option-info">
                                    <span class="cookie-option-title">Analytics Cookies</span>
                                    <span class="cookie-option-desc">These cookies help us understand how visitors interact with our website, allowing us to measure and improve performance.</span>
                                </div>
                                <label class="cookie-switch">
                                    <input type="checkbox" id="siksik-cookie-analytic-chk">
                                    <span class="cookie-slider"></span>
                                </label>
                            </div>
                            
                            <div class="cookie-option-item">
                                <div class="cookie-option-info">
                                    <span class="cookie-option-title">Marketing Cookies</span>
                                    <span class="cookie-option-desc">These cookies are used to track visitor behavior across websites to display relevant, engaging ads.</span>
                                </div>
                                <label class="cookie-switch">
                                    <input type="checkbox" id="siksik-cookie-marketing-chk">
                                    <span class="cookie-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="cookie-banner-actions">
                            <button class="cookie-btn cookie-btn-link" id="siksik-cookie-back-btn">Go Back</button>
                            <button class="cookie-btn cookie-btn-primary" id="siksik-cookie-save">Save Choices</button>
                        </div>
                    </div>
                </div>
            </div>

            <button id="siksik-cookie-trigger-btn" class="cookie-trigger-fab" title="Cookie Preferences">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20">
                    <path fill="#00d4a8" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm-1.5 5a1.5 1.5 0 1 1-1.5 1.5A1.5 1.5 0 0 1 10.5 7zm-3 5a1.5 1.5 0 1 1-1.5 1.5A1.5 1.5 0 0 1 7.5 12zm2.5 5a1.5 1.5 0 1 1-1.5 1.5A1.5 1.5 0 0 1 10 17zm5.5 0a1.5 1.5 0 1 1-1.5-1.5 1.5 1.5 0 0 1 1.5 1.5zm.5-4.5a1.5 1.5 0 1 1-1.5-1.5 1.5 1.5 0 0 1 1.5 1.5zm-1.5-4a1.5 1.5 0 1 1 1.5 1.5 1.5 0 0 1-1.5-1.5zm3.5 2.5a.5.5 0 1 1-.5-.5.5.5 0 0 1 .5.5z"/>
                </svg>
            </button>
        `;

        const wrapper = document.createElement('div');
        wrapper.innerHTML = bannerHtml.trim();
        while (wrapper.firstChild) {
            document.body.appendChild(wrapper.firstChild);
        }

        setupEventListeners();
    }

    function setupEventListeners() {
        const banner = document.getElementById('siksik-cookie-banner');
        const mainView = document.getElementById('siksik-cookie-main-view');
        const settingsView = document.getElementById('siksik-cookie-settings-view');
        const triggerBtn = document.getElementById('siksik-cookie-trigger-btn');

        const closeBtn = document.getElementById('siksik-cookie-close');
        const acceptBtn = document.getElementById('siksik-cookie-accept');
        const rejectBtn = document.getElementById('siksik-cookie-reject');
        const customizeBtn = document.getElementById('siksik-cookie-customize-btn');
        const backBtn = document.getElementById('siksik-cookie-back-btn');
        const saveBtn = document.getElementById('siksik-cookie-save');

        const prefChk = document.getElementById('siksik-cookie-pref-chk');
        const analyticChk = document.getElementById('siksik-cookie-analytic-chk');
        const marketingChk = document.getElementById('siksik-cookie-marketing-chk');

        function loadSavedToggles() {
            const savedPrefs = getCookie('siksik_cookie_preferences');
            if (savedPrefs) {
                try {
                    const prefs = JSON.parse(savedPrefs);
                    prefChk.checked = !!prefs.preferences;
                    analyticChk.checked = !!prefs.analytics;
                    marketingChk.checked = !!prefs.marketing;
                } catch (e) {
                    console.error("Failed to parse cookie preferences", e);
                }
            }
        }

        acceptBtn.addEventListener('click', function () {
            const preferences = {
                necessary: true,
                preferences: true,
                analytics: true,
                marketing: true
            };
            setCookie('siksik_cookie_consent', 'accepted', 365);
            setCookie('siksik_cookie_preferences', JSON.stringify(preferences), 365);
            hideBanner();
        });

        rejectBtn.addEventListener('click', function () {
            const preferences = {
                necessary: true,
                preferences: false,
                analytics: false,
                marketing: false
            };
            setCookie('siksik_cookie_consent', 'rejected', 365);
            setCookie('siksik_cookie_preferences', JSON.stringify(preferences), 365);
            hideBanner();
        });

        customizeBtn.addEventListener('click', function () {
            loadSavedToggles();
            mainView.classList.add('cookie-banner-hidden');
            settingsView.classList.remove('cookie-banner-hidden');
        });

        backBtn.addEventListener('click', function () {
            settingsView.classList.add('cookie-banner-hidden');
            mainView.classList.remove('cookie-banner-hidden');
        });

        saveBtn.addEventListener('click', function () {
            const preferences = {
                necessary: true,
                preferences: prefChk.checked,
                analytics: analyticChk.checked,
                marketing: marketingChk.checked
            };
            setCookie('siksik_cookie_consent', 'custom', 365);
            setCookie('siksik_cookie_preferences', JSON.stringify(preferences), 365);
            hideBanner();
        });

        closeBtn.addEventListener('click', function () {
            hideBanner();
        });

        triggerBtn.addEventListener('click', function () {
            showBanner();
            loadSavedToggles();
            if (getCookie('siksik_cookie_consent') === 'custom') {
                mainView.classList.add('cookie-banner-hidden');
                settingsView.classList.remove('cookie-banner-hidden');
            } else {
                settingsView.classList.add('cookie-banner-hidden');
                mainView.classList.remove('cookie-banner-hidden');
            }
        });
    }

    function showBanner() {
        const banner = document.getElementById('siksik-cookie-banner');
        if (banner) {
            banner.classList.remove('cookie-banner-hidden');
            void banner.offsetWidth;
            banner.classList.add('cookie-banner-visible');
        }
    }

    function hideBanner() {
        const banner = document.getElementById('siksik-cookie-banner');
        if (banner) {
            banner.classList.remove('cookie-banner-visible');
            banner.classList.add('cookie-banner-hidden');
        }
    }

    injectCookieBanner();

    const consent = getCookie('siksik_cookie_consent');
    if (!consent) {
        setTimeout(showBanner, 800);
    }
});
