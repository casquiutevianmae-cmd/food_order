<?php
$page_title = "Task Estimator - Event Catering & Food Calculator";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Sleek Hero Banner -->
    <div class="estimator-hero-banner mb-4 d-print-none">
        <div class="row align-items-center g-3">
            <div class="col-lg-8 text-center text-lg-start">
                <div class="d-inline-flex align-items-center gap-2 mb-2 estimator-badge">
                    <i class="bi bi-stars text-primary"></i> Interactive Catering Suite
                </div>
                <h2 class="display-6 fw-bold mb-0 font-heading hero-title">
                    Event Catering & Order Estimator
                </h2>
            </div>
            <div class="col-lg-4 d-none d-lg-flex justify-content-end gap-2">
                <div class="badge bg-primary bg-opacity-10 p-3 rounded-4 text-start border border-primary border-opacity-25 d-flex align-items-center gap-3">
                    <div class="bg-primary rounded-circle p-2 text-white fs-4 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-calculator-fill"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark small">Instant Quotes</div>
                        <div class="extra-small text-muted">No page reloads needed</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row g-4">
        <!-- Input Form Section (Left Column) -->
        <div class="col-lg-6 d-print-none">
            <div class="glass-estimator-card h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between rounded-top-4">
                    <h5 class="card-title fw-bold mb-0 d-flex align-items-center gap-2 font-heading text-dark fs-5">
                        <i class="bi bi-sliders text-primary fs-5"></i> Order Details & Customization
                    </h5>
                    <span class="badge bg-light text-dark border border-secondary-subtle fw-bold px-3 py-2 rounded-pill shadow-sm fs-6">Step 1 of 2</span>
                </div>
                
                <div class="card-body p-4">
                    <!-- Alert Message Container for Validation Errors -->
                    <div id="validationAlert" class="alert alert-danger d-none align-items-center gap-3 rounded-4 shadow-sm mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-3 flex-shrink-0 text-danger"></i>
                        <div id="validationAlertText" class="fw-medium">Please fill out all required fields correctly.</div>
                    </div>

                    <form id="cateringTaskForm" class="needs-validation" novalidate onsubmit="return false;">
                        
                        <!-- Field 1: Customer Name -->
                        <div class="mb-4">
                            <label for="customerName" class="form-label fw-bold text-dark fs-6 mb-2">
                                <i class="bi bi-person-fill me-1 text-primary"></i> Full Name <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light border-end-0 rounded-start-3 text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control form-control-glow border-start-0 fs-6 text-dark" id="customerName" placeholder="e.g. Sarah Jenkins" required>
                            </div>
                            <div class="invalid-feedback">Please enter your full name.</div>
                        </div>

                        <!-- Field 2: Meal Package Selection -->
                        <div class="mb-4">
                            <label for="mealPackage" class="form-label fw-bold text-dark fs-6 mb-2">
                                <i class="bi bi-egg-fried me-1 text-primary"></i> Select Meal Package <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-glow form-select-lg fs-6 text-dark" id="mealPackage" required>
                                <option value="" selected disabled>-- Choose a package --</option>
                                <option value="12.50" data-name="Classic Burger Party Pack">Classic Burger Party Pack (₱12.50 / person)</option>
                                <option value="18.00" data-name="Deluxe Feast Combo">Deluxe Feast Combo (₱18.00 / person)</option>
                                <option value="25.00" data-name="Gourmet BBQ & Wings Banquet">Gourmet BBQ & Wings Banquet (₱25.00 / person)</option>
                                <option value="15.00" data-name="Family Snack & Drinks Platter">Family Snack & Drinks Platter (₱15.00 / person)</option>
                            </select>
                            <div class="invalid-feedback">Please select a meal package.</div>
                        </div>

                        <!-- Field 3: Number of Guests / Quantity -->
                        <div class="mb-4">
                            <label for="guestCount" class="form-label fw-bold text-dark fs-6 mb-1">
                                <i class="bi bi-people-fill me-1 text-primary"></i> Number of Guests / Quantity <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-lg mb-2">
                                <span class="input-group-text bg-light border-end-0 rounded-start-3 text-muted"><i class="bi bi-hash"></i></span>
                                <input type="number" class="form-control form-control-glow border-start-0 fs-6 text-dark" id="guestCount" placeholder="e.g. 25" min="1" max="500" required>
                            </div>
                            
                            <!-- Quick Guest Count Chips -->
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="small text-muted fw-semibold me-1">Quick Select:</span>
                                <div class="guest-chip-group">
                                    <button type="button" class="guest-chip" data-count="10">10 Guests</button>
                                    <button type="button" class="guest-chip" data-count="25">25 Guests</button>
                                    <button type="button" class="guest-chip" data-count="50">50 Guests</button>
                                    <button type="button" class="guest-chip" data-count="100">100 Guests</button>
                                </div>
                            </div>
                            
                            <div class="form-text text-primary small d-flex align-items-center gap-1">
                                <i class="bi bi-info-circle-fill"></i> Orders for 10 or more guests automatically qualify for a 10% bulk discount!
                            </div>
                            <div class="invalid-feedback">Please enter a valid guest count (1 - 500).</div>
                        </div>

                        <!-- Field 4: Service / Delivery Option -->
                        <div class="mb-4">
                            <label for="serviceOption" class="form-label fw-bold text-dark fs-6 mb-2">
                                <i class="bi bi-truck me-1 text-primary"></i> Delivery & Service Option
                            </label>
                            <select class="form-select form-select-glow form-select-lg fs-6 text-dark" id="serviceOption">
                                <option value="0" data-name="Standard Pickup">Standard Pickup (Free - ₱0.00)</option>
                                <option value="5.99" data-name="Express Local Delivery">Express Local Delivery (+₱5.99)</option>
                                <option value="19.99" data-name="VIP Full Setup & Delivery">VIP Full Setup & Delivery (+₱19.99)</option>
                            </select>
                        </div>

                        <!-- Field 5: Promo Code (Optional) -->
                        <div class="mb-4">
                            <label for="promoCode" class="form-label fw-bold text-dark fs-6 mb-2">
                                <i class="bi bi-ticket-perforated-fill me-1 text-primary"></i> Promo Code
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control form-control-glow text-uppercase text-dark" id="promoCode" placeholder="Try code: YUM10 or SAVE20">
                                <button class="btn btn-outline-primary px-4 fw-bold" type="button" id="applyPromoBtn">Apply</button>
                            </div>
                            <div id="promoFeedback" class="form-text mt-1 fw-medium"></div>
                        </div>

                        <!-- Form Action Buttons -->
                        <div class="d-grid gap-2 d-md-flex justify-md-content-end pt-2">
                            <button type="button" id="resetBtn" class="btn btn-light btn-lg px-4 border rounded-3 text-secondary fw-semibold">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                            </button>
                            <button type="submit" id="processBtn" class="btn btn-primary btn-lg px-4 shadow rounded-3 flex-grow-1 fw-bold text-white" style="background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%); border: none;">
                                <i class="bi bi-lightning-charge-fill me-1 text-white"></i> Process Estimate
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- Result Section (Right Column) -->
        <div class="col-lg-6">
            
            <!-- Default Placeholder Container when no calculation has occurred -->
            <div id="emptyStateContainer" class="glass-estimator-card h-100 text-center py-5 px-4 d-flex align-items-center justify-content-center">
                <div class="my-auto">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex p-4 mb-3 fs-1 shadow-sm">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2 font-heading">No Calculation Processed Yet</h5>
                    <p class="text-muted small mx-auto mb-0" style="max-width: 330px;">
                        Complete the order configuration on the left and click <strong>"Process Estimate"</strong> to generate your itemized breakdown instantly.
                    </p>
                </div>
            </div>

            <!-- Dynamic Result Output Card (Initially Hidden with Bootstrap d-none) -->
            <div id="resultContainer" class="glass-estimator-card h-100 d-none">
                
                <div class="card-header-success text-white d-flex align-items-center justify-content-between">
                    <h5 class="card-title fw-bold mb-0 d-flex align-items-center gap-2 font-heading">
                        <i class="bi bi-check-circle-fill text-info"></i> Order Summary & Breakdown
                    </h5>
                    <span class="badge bg-primary text-white fw-bold px-3 py-2 rounded-pill shadow-sm" id="resultTimestamp"></span>
                </div>

                <div class="card-body p-4">
                    
                    <!-- Confirmation Message Banner -->
                    <div class="alert alert-primary d-flex align-items-center gap-3 rounded-4 shadow-sm mb-4 border-0 bg-primary bg-opacity-10">
                        <i class="bi bi-bag-check-fill fs-2 flex-shrink-0 text-primary"></i>
                        <div>
                            <h6 class="fw-bold mb-1 text-primary" id="greetingHeader">Estimate Ready!</h6>
                            <p class="mb-0 small text-primary-emphasis text-dark" id="confirmationMessageText">Your catering quote has been successfully generated.</p>
                        </div>
                    </div>

                    <!-- Order Details Summary Table -->
                    <h6 class="fw-bold text-dark mb-3 border-bottom pb-2 font-heading">
                        <i class="bi bi-receipt me-1 text-primary"></i> Itemized Cost Breakdown
                    </h6>

                    <ul class="list-group cost-breakdown-list mb-4 rounded-4 border overflow-hidden">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><strong id="outPackageName" class="text-dark">Selected Package</strong> (<span id="outGuestCount" class="fw-semibold text-primary">0</span> guests)</span>
                            <span class="fw-bold text-dark fs-6" id="outPackageCost">₱0.00</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-dark">Service & Delivery (<span id="outServiceName" class="text-primary">Standard</span>)</span>
                            <span class="fw-semibold text-dark" id="outServiceCost">₱0.00</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-light">
                            <span class="fw-medium text-dark">Gross Subtotal</span>
                            <span class="fw-bold text-dark" id="outSubtotal">₱0.00</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center text-primary bg-primary bg-opacity-10" id="discountRow">
                            <span class="fw-semibold"><i class="bi bi-tag-fill me-1"></i> Discount Applied (<span id="outDiscountLabel">0%</span>)</span>
                            <span class="fw-bold fs-6 text-primary" id="outDiscountCost">-₱0.00</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center text-dark">
                            <span>Estimated Sales Tax (8%)</span>
                            <span class="fw-semibold text-dark" id="outTaxCost">₱0.00</span>
                        </li>
                        <li class="list-group-item p-0">
                            <div class="grand-total-box d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="fs-5 text-dark font-heading">Estimated Total:</strong>
                                    <div class="extra-small text-muted">All estimated taxes & fees included</div>
                                </div>
                                <span class="fs-2 fw-bold text-primary font-heading" id="outGrandTotal">₱0.00</span>
                            </div>
                        </li>
                    </ul>
                                    <div class="extra-small text-muted">All estimated taxes & fees included</div>
                                </div>
                                <span class="fs-2 fw-bold text-primary font-heading" id="outGrandTotal">₱0.00</span>
                            </div>
                        </li>
                    </ul>

                    <!-- Dynamic Delivery & Prep Time Badge -->
                    <div class="p-3 bg-light rounded-4 border d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 fs-4 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div>
                                <div class="fw-bold small text-dark">Estimated Preparation Time</div>
                                <div class="small text-muted" id="outPrepTime">30 - 45 Minutes</div>
                            </div>
                        </div>
                        <span class="badge bg-primary text-white px-3 py-2 rounded-pill shadow-sm" id="outBadgeStatus">Ready to Order</span>
                    </div>

                </div>

                <div class="card-footer bg-white border-top p-3 rounded-bottom-4 d-flex justify-content-between align-items-center">
                    <span class="extra-small text-muted"><i class="bi bi-shield-check text-success me-1"></i> Client-side JavaScript Processed</span>
                    <button class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold d-print-none" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print Estimate
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Embedded JavaScript for Data Processing, Validation, and Interactivity -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('cateringTaskForm');
    const customerNameInput = document.getElementById('customerName');
    const mealPackageSelect = document.getElementById('mealPackage');
    const guestCountInput = document.getElementById('guestCount');
    const serviceOptionSelect = document.getElementById('serviceOption');
    const promoCodeInput = document.getElementById('promoCode');
    const applyPromoBtn = document.getElementById('applyPromoBtn');
    const processBtn = document.getElementById('processBtn');
    const resetBtn = document.getElementById('resetBtn');

    // Display elements
    const validationAlert = document.getElementById('validationAlert');
    const validationAlertText = document.getElementById('validationAlertText');
    const emptyStateContainer = document.getElementById('emptyStateContainer');
    const resultContainer = document.getElementById('resultContainer');
    const promoFeedback = document.getElementById('promoFeedback');
    const discountRow = document.getElementById('discountRow');

    // Result output targets
    const greetingHeader = document.getElementById('greetingHeader');
    const confirmationMessageText = document.getElementById('confirmationMessageText');
    const resultTimestamp = document.getElementById('resultTimestamp');
    const outPackageName = document.getElementById('outPackageName');
    const outGuestCount = document.getElementById('outGuestCount');
    const outPackageCost = document.getElementById('outPackageCost');
    const outServiceName = document.getElementById('outServiceName');
    const outServiceCost = document.getElementById('outServiceCost');
    const outSubtotal = document.getElementById('outSubtotal');
    const outDiscountLabel = document.getElementById('outDiscountLabel');
    const outDiscountCost = document.getElementById('outDiscountCost');
    const outTaxCost = document.getElementById('outTaxCost');
    const outGrandTotal = document.getElementById('outGrandTotal');
    const outPrepTime = document.getElementById('outPrepTime');
    const outBadgeStatus = document.getElementById('outBadgeStatus');

    let appliedPromoDiscount = 0; // percentage discount if promo code is applied
    let activePromoCode = '';

    // Clear validation styling when user interacts with inputs
    [customerNameInput, mealPackageSelect, guestCountInput].forEach(field => {
        field.addEventListener('input', function () {
            this.classList.remove('is-invalid');
        });
        field.addEventListener('change', function () {
            this.classList.remove('is-invalid');
        });
    });

    // Quick Select Guest Chips
    document.querySelectorAll('.guest-chip').forEach(chip => {
        chip.addEventListener('click', function () {
            const count = this.getAttribute('data-count');
            guestCountInput.value = count;
            guestCountInput.classList.remove('is-invalid');
            
            // Re-process estimate if result is currently visible
            if (!resultContainer.classList.contains('d-none')) {
                processEstimate();
            }
        });
    });

    // Helper to evaluate promo code string
    function evaluatePromoCode(code) {
        code = code.trim().toUpperCase();
        if (code === 'YUM10') {
            return { discount: 0.10, code: 'YUM10', valid: true, msg: '<i class="bi bi-check-circle me-1"></i> Promo code YUM10 applied (10% OFF)!' };
        } else if (code === 'SAVE20') {
            return { discount: 0.20, code: 'SAVE20', valid: true, msg: '<i class="bi bi-check-circle me-1"></i> Promo code SAVE20 applied (20% OFF)!' };
        } else if (code === '') {
            return { discount: 0, code: '', valid: true, msg: '' };
        } else {
            return { discount: 0, code: code, valid: false, msg: '<i class="bi bi-x-circle me-1"></i> Invalid promo code. Try YUM10 or SAVE20.' };
        }
    }

    // Apply Promo Code handler button
    applyPromoBtn.addEventListener('click', function () {
        const result = evaluatePromoCode(promoCodeInput.value);
        appliedPromoDiscount = result.discount;
        activePromoCode = result.valid ? result.code : '';

        if (result.msg === '') {
            promoFeedback.className = 'form-text text-muted';
            promoFeedback.textContent = '';
        } else if (result.valid) {
            promoFeedback.className = 'form-text text-success fw-semibold';
            promoFeedback.innerHTML = result.msg;
        } else {
            promoFeedback.className = 'form-text text-danger fw-semibold';
            promoFeedback.innerHTML = result.msg;
        }

        // Re-process estimate if result is currently visible
        if (!resultContainer.classList.contains('d-none')) {
            processEstimate();
        }
    });

    // Form Submission & Calculation
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        processEstimate();
    });

    function processEstimate() {
        // 1. Check & Sync Promo Code if typed without clicking "Apply" button
        const promoVal = promoCodeInput.value.trim();
        if (promoVal !== '') {
            const promoResult = evaluatePromoCode(promoVal);
            appliedPromoDiscount = promoResult.discount;
            activePromoCode = promoResult.valid ? promoResult.code : '';
            if (promoResult.valid) {
                promoFeedback.className = 'form-text text-success fw-semibold';
                promoFeedback.innerHTML = promoResult.msg;
            } else {
                promoFeedback.className = 'form-text text-danger fw-semibold';
                promoFeedback.innerHTML = promoResult.msg;
            }
        }

        // 2. JavaScript Field Validation
        let errors = [];
        customerNameInput.classList.remove('is-invalid');
        mealPackageSelect.classList.remove('is-invalid');
        guestCountInput.classList.remove('is-invalid');

        const nameValue = customerNameInput.value.trim();
        const packagePrice = parseFloat(mealPackageSelect.value);
        const guestCount = parseInt(guestCountInput.value, 10);

        if (!nameValue) {
            customerNameInput.classList.add('is-invalid');
            errors.push('Full Name is required.');
        }

        if (isNaN(packagePrice) || packagePrice <= 0) {
            mealPackageSelect.classList.add('is-invalid');
            errors.push('Please select a valid meal package.');
        }

        if (isNaN(guestCount) || guestCount < 1 || guestCount > 500) {
            guestCountInput.classList.add('is-invalid');
            errors.push('Please enter a valid guest count between 1 and 500.');
        }

        if (errors.length > 0) {
            // Show validation error alert
            validationAlertText.innerHTML = errors.join('<br>');
            validationAlert.classList.remove('d-none');
            validationAlert.classList.add('d-flex');
            
            // Hide result container and show empty state
            resultContainer.classList.add('d-none');
            emptyStateContainer.classList.remove('d-none');
            return;
        }

        // Hide validation alert if valid
        validationAlert.classList.add('d-none');
        validationAlert.classList.remove('d-flex');

        // 3. JavaScript Computation
        const selectedPackageOption = mealPackageSelect.options[mealPackageSelect.selectedIndex];
        const packageName = selectedPackageOption.getAttribute('data-name') || 'Selected Package';

        const servicePrice = parseFloat(serviceOptionSelect.value) || 0;
        const selectedServiceOption = serviceOptionSelect.options[serviceOptionSelect.selectedIndex];
        const serviceName = selectedServiceOption.getAttribute('data-name') || 'Standard Service';

        const rawFoodSubtotal = packagePrice * guestCount;
        const grossSubtotal = rawFoodSubtotal + servicePrice;

        // Determine combined discount rates (Bulk 10% for >=10 guests + Promo Code discount)
        let bulkDiscountRate = (guestCount >= 10) ? 0.10 : 0;
        let totalDiscountRate = bulkDiscountRate + appliedPromoDiscount;

        let discountParts = [];
        if (bulkDiscountRate > 0) discountParts.push('10% Bulk Discount');
        if (appliedPromoDiscount > 0 && activePromoCode) discountParts.push(`${(appliedPromoDiscount * 100).toFixed(0)}% Promo (${activePromoCode})`);

        let discountReason = discountParts.length > 0 
            ? `${(totalDiscountRate * 100).toFixed(0)}% (${discountParts.join(' + ')})`
            : '0%';

        const discountAmount = grossSubtotal * totalDiscountRate;
        const taxableTotal = Math.max(0, grossSubtotal - discountAmount);
        const taxAmount = taxableTotal * 0.08; // 8% tax
        const grandTotal = taxableTotal + taxAmount;

        // Estimated prep time calculation based on guest count scale
        let prepMinutes = '30 - 45 Mins';
        if (guestCount > 100) prepMinutes = '2 - 3 Hours';
        else if (guestCount > 50) prepMinutes = '1 - 2 Hours';
        else if (guestCount > 20) prepMinutes = '45 - 60 Mins';

        // Dynamic Badge Status based on guest count & service type
        let statusBadgeText = 'Standard Order';
        if (guestCount >= 50) {
            statusBadgeText = 'Large Event Catering';
        } else if (servicePrice === 0) {
            statusBadgeText = 'In-Store Pickup';
        } else if (servicePrice > 15) {
            statusBadgeText = 'VIP Setup & Delivery';
        } else {
            statusBadgeText = 'Express Delivery';
        }

        // 4. Dynamic Text & Page Content Updates
        greetingHeader.textContent = `Hello, ${nameValue}!`;
        confirmationMessageText.textContent = `Your catering estimate for ${guestCount} guests (${packageName}) has been successfully processed!`;
        
        const now = new Date();
        resultTimestamp.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

        outPackageName.textContent = packageName;
        outGuestCount.textContent = guestCount;
        outPackageCost.textContent = `₱${rawFoodSubtotal.toFixed(2)}`;

        outServiceName.textContent = serviceName;
        outServiceCost.textContent = servicePrice === 0 ? 'FREE' : `₱${servicePrice.toFixed(2)}`;

        outSubtotal.textContent = `₱${grossSubtotal.toFixed(2)}`;

        // Show/Hide Discount Row dynamically
        if (discountAmount > 0) {
            discountRow.classList.remove('d-none');
            discountRow.classList.add('d-flex');
            outDiscountLabel.textContent = discountReason;
            outDiscountCost.textContent = `-₱${discountAmount.toFixed(2)}`;
        } else {
            discountRow.classList.add('d-none');
            discountRow.classList.remove('d-flex');
        }

        outTaxCost.textContent = `₱${taxAmount.toFixed(2)}`;
        outGrandTotal.textContent = `₱${grandTotal.toFixed(2)}`;
        outPrepTime.textContent = prepMinutes;
        outBadgeStatus.textContent = statusBadgeText;

        // 5. Element Show/Hide Dynamics & Pulse Effect
        emptyStateContainer.classList.add('d-none');
        resultContainer.classList.remove('d-none');
        resultContainer.classList.add('pulse-update');
        setTimeout(() => resultContainer.classList.remove('pulse-update'), 500);

        // Scroll smoothly to results on mobile view
        if (window.innerWidth < 992) {
            resultContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // Reset Form Handler
    resetBtn.addEventListener('click', function () {
        form.reset();
        customerNameInput.classList.remove('is-invalid');
        mealPackageSelect.classList.remove('is-invalid');
        guestCountInput.classList.remove('is-invalid');
        
        appliedPromoDiscount = 0;
        activePromoCode = '';
        promoFeedback.textContent = '';
        
        // Hide alert & result, show empty state
        validationAlert.classList.add('d-none');
        validationAlert.classList.remove('d-flex');
        resultContainer.classList.add('d-none');
        emptyStateContainer.classList.remove('d-none');
        
        customerNameInput.focus();
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
