/**
 * Social Welfare Scheme Management System
 * Main JavaScript - Glassmorphism UI
 */

document.addEventListener('DOMContentLoaded', function () {

    /* ============================================================
       1. SIDEBAR TOGGLE
    ============================================================ */
    const sidebar = document.getElementById('sidebar');
    const mainWrapper = document.getElementById('mainWrapper');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    function isMobile() { return window.innerWidth <= 768; }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            if (isMobile()) {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                mainWrapper.classList.toggle('expanded');
            }
        });
    }

    overlay.addEventListener('click', function () {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('show');
    });

    /* ============================================================
       2. DARK MODE TOGGLE
    ============================================================ */
    const darkToggle = document.getElementById('darkToggle');
    const darkIcon = document.getElementById('darkIcon');
    const isDark = document.cookie.includes('dark_mode=1');

    if (isDark) {
        document.body.classList.add('dark-mode');
        if (darkIcon) darkIcon.className = 'fas fa-sun';
    }

    if (darkToggle) {
        darkToggle.addEventListener('click', function () {
            document.body.classList.toggle('dark-mode');
            const dark = document.body.classList.contains('dark-mode');
            document.cookie = `dark_mode=${dark ? 1 : 0};path=/;max-age=31536000`;
            if (darkIcon) {
                darkIcon.className = dark ? 'fas fa-sun' : 'fas fa-moon';
            }
        });
    }

    /* ============================================================
       3. SCROLL ANIMATIONS
    ============================================================ */
    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });

    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        el.style.opacity = '0';
        observer.observe(el);
    });

    /* ============================================================
       4. MARK NOTIFICATIONS AS READ
    ============================================================ */
    const url = new URL(window.location.href);
    if (url.searchParams.get('mark_read') === '1') {
        fetch('?ajax_mark_read=1')
            .then(() => {
                document.querySelectorAll('.notif-badge').forEach(b => b.remove());
                document.querySelectorAll('.notif-item.unread').forEach(i => i.classList.remove('unread'));
                url.searchParams.delete('mark_read');
                window.history.replaceState({}, '', url);
            });
    }

    /* ============================================================
       5. REGISTRATION FORM VALIDATION
    ============================================================ */
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        const nameField = document.getElementById('name');
        const emailField = document.getElementById('reg_email');
        const phoneField = document.getElementById('phone');
        const dobField = document.getElementById('dob');
        const incomeField = document.getElementById('annual_income');
        const addressField = document.getElementById('address');
        const passwordField = document.getElementById('password');
        const confirmField = document.getElementById('confirm_password');
        const phoneRegex = /^[6-9]\d{9}$/;

        function getAge(dobStr) {
            if (!dobStr) return NaN;
            const dob = new Date(dobStr + 'T00:00:00');
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const m = today.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
            return age;
        }

        // Real-time: Name - only alphabets and spaces
        if (nameField) {
            nameField.addEventListener('input', function () {
                const nameRegex = /^[A-Za-z ]+$/;
                if (!nameRegex.test(this.value) && this.value !== '') {
                    showFieldError(this, 'Name must contain only alphabets and spaces.');
                } else {
                    clearFieldError(this);
                }
            });
        }

        // Real-time: Email format
        if (emailField) {
            emailField.addEventListener('input', function () {
                const v = this.value.trim();
        
                // stricter check
                const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-z]{2,}$/;
        
                if (!v) {
                    showFieldError(this, 'Email is required.');
                } else if (!emailRegex.test(v)) {
                    showFieldError(this, 'Enter a valid email (e.g., user@gmail.com).');
                } else {
                    clearFieldError(this);
                }
            });
        }

        // Real-time: Phone - 10 digits
        if (phoneField) {
            phoneField.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
                if (this.value && !/^[6-9]/.test(this.value)) {
                    showFieldError(this, 'Phone must start with 6, 7, 8, or 9.');
                } else {
                    clearFieldError(this);
                }
            });
        }

        // Real-time: DOB must be in the past + 18+
        if (dobField) {
            dobField.max = new Date().toISOString().split('T')[0];
        
            dobField.addEventListener('input', function () {
                const dob = new Date(this.value);
                const today = new Date();
        
                if (!this.value || dob >= today) {
                    showFieldError(this, 'Enter a valid past date.');
                    return;
                }
        
                const age = today.getFullYear() - dob.getFullYear();
                const m = today.getMonth() - dob.getMonth();
        
                const is18 =
                    age > 18 || (age === 18 && (m > 0 || (m === 0 && today.getDate() >= dob.getDate())));
        
                if (!is18) {
                    showFieldError(this, 'You must be at least 18 years old.');
                } else {
                    clearFieldError(this);
                }
            });
        }

        // Real-time: Income (numeric, min 50000)
        if (incomeField) {
            incomeField.addEventListener('input', function () {
                const raw = this.value.trim();
                const n = Number(raw);
                if (!raw) {
                    showFieldError(this, 'Annual income is required.');
                } else if (!Number.isFinite(n)) {
                    showFieldError(this, 'Annual income must be a numeric value.');
                } else if (n < 50000) {
                    showFieldError(this, 'Annual income must be at least ₹50000.');
                } else {
                    clearFieldError(this);
                }
            });
        }

        // Real-time: Address (required, min 10 chars)
        if (addressField) {
            addressField.addEventListener('input', function () {
                const v = this.value.trim();
        
                // Must contain letters + length
                const validAddress = /^[a-zA-Z0-9\s,.-]{10,}$/;
        
                if (!v) {
                    showFieldError(this, 'Address is required.');
                } else if (!validAddress.test(v)) {
                    showFieldError(this, 'Enter a valid address (min 10 characters).');
                } else {
                    clearFieldError(this);
                }
            });
        }
        // Real-time: Password strength
        if (passwordField) {
            passwordField.addEventListener('input', function () {
                const pw = this.value;
                const hasUpper = /[A-Z]/.test(pw);
                const hasNum = /[0-9]/.test(pw);
                const hasLen = pw.length >= 8;
                updateStrengthIndicator(pw, hasUpper, hasNum, hasLen);

                if (pw && !(hasLen && hasUpper && hasNum)) {
                    showFieldError(this, 'Password must be 8+ chars, 1 uppercase, 1 number.');
                } else {
                    clearFieldError(this);
                }
            });
        }

        registerForm.addEventListener('submit', function (e) {
            let valid = true;

            if (nameField) {
                const nameRegex = /^[A-Za-z ]+$/;
                if (!nameRegex.test(nameField.value.trim())) {
                    showFieldError(nameField, 'Name must contain only alphabets and spaces.');
                    valid = false;
                }
            }

            if (emailField) {
                const v = emailField.value.trim();
                if (!v) {
                    showFieldError(emailField, 'Email is required.');
                    valid = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) {
                    showFieldError(emailField, 'Enter a valid email (e.g., user@gmail.com).');
                    valid = false;
                }
            }

            if (phoneField && !phoneRegex.test(phoneField.value.trim())) {
                showFieldError(phoneField, 'Phone must be exactly 10 digits and start with 6-9.');
                valid = false;
            }

            if (dobField) {
                const dob = new Date(dobField.value);
                const today = new Date();
            
                if (!dobField.value || dob >= today) {
                    showFieldError(dobField, 'Enter a valid past date.');
                    valid = false;
                } else {
                    const age = today.getFullYear() - dob.getFullYear();
                    const m = today.getMonth() - dob.getMonth();
            
                    const is18 =
                        age > 18 || (age === 18 && (m > 0 || (m === 0 && today.getDate() >= dob.getDate())));
            
                    if (!is18) {
                        showFieldError(dobField, 'You must be at least 18 years old.');
                        valid = false;
                    }
                }
            }

            if (incomeField) {
                const raw = incomeField.value.trim();
                const n = Number(raw);
                if (!raw) {
                    showFieldError(incomeField, 'Annual income is required.');
                    valid = false;
                } else if (!Number.isFinite(n)) {
                    showFieldError(incomeField, 'Annual income must be a numeric value.');
                    valid = false;
                } else if (n < 50000) {
                    showFieldError(incomeField, 'Annual income must be at least ₹50000.');
                    valid = false;
                }
            }

            if (addressField) {
                const v = addressField.value.trim();
                if (!v) {
                    showFieldError(addressField, 'Address is required.');
                    valid = false;
                } else if (v.length < 10) {
                    showFieldError(addressField, 'Address must be at least 10 characters.');
                    valid = false;
                }
            }

            if (passwordField) {
                const pw = passwordField.value;
                if (pw.length < 8 || !/[A-Z]/.test(pw) || !/[0-9]/.test(pw)) {
                    showFieldError(passwordField, 'Password must be 8+ chars with uppercase and number.');
                    valid = false;
                }
            }

            if (confirmField && passwordField && confirmField.value !== passwordField.value) {
                showFieldError(confirmField, 'Passwords do not match.');
                valid = false;
            }

            if (!valid) e.preventDefault();
        });
    }

    /* ============================================================
       5B. PROFILE FORM VALIDATION
    ============================================================ */
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        const phoneField = profileForm.querySelector('[name="phone"]');
        const dobField = profileForm.querySelector('[name="dob"]');
        const incomeField = profileForm.querySelector('[name="annual_income"]');
        const addressField = profileForm.querySelector('[name="address"]');
        const phoneRegex = /^[6-9]\d{9}$/;

        function getAge(dobStr) {
            if (!dobStr) return NaN;
            const dob = new Date(dobStr + 'T00:00:00');
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const m = today.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
            return age;
        }

        if (phoneField) {
            phoneField.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 10);
                if (this.value && !/^[6-9]/.test(this.value)) {
                    showFieldError(this, 'Phone must start with 6, 7, 8, or 9.');
                } else {
                    clearFieldError(this);
                }
            });
        }

        if (dobField) {
            dobField.addEventListener('input', function () {
              const dob = new Date(this.value);
              const today = new Date();
          
              const age = today.getFullYear() - dob.getFullYear();
              const m = today.getMonth() - dob.getMonth();
          
              const is18 =
                age > 18 || (age === 18 && (m > 0 || (m === 0 && today.getDate() >= dob.getDate())));
          
              if (!is18) {
                showFieldError(this, 'You must be at least 18 years old');
              } else {
                clearFieldError(this);
              }
            });
          
            dobField.max = new Date().toISOString().split('T')[0];
          }

        if (incomeField) {
            incomeField.addEventListener('input', function () {
                const raw = this.value.trim();
                const n = Number(raw);
                if (!raw) {
                    showFieldError(this, 'Annual income is required.');
                } else if (!Number.isFinite(n)) {
                    showFieldError(this, 'Annual income must be a numeric value.');
                } else if (n < 50000) {
                    showFieldError(this, 'Annual income must be at least ₹50000.');
                } else {
                    clearFieldError(this);
                }
            });
        }

        if (addressField) {
            addressField.addEventListener('input', function () {
                const v = this.value.trim();
                if (!v) {
                    showFieldError(this, 'Address is required.');
                } else if (v.length < 10) {
                    showFieldError(this, 'Address must be at least 10 characters.');
                } else {
                    clearFieldError(this);
                }
            });
        }

        profileForm.addEventListener('submit', function (e) {
            let valid = true;

            if (phoneField && !phoneRegex.test(phoneField.value.trim())) {
                showFieldError(phoneField, 'Phone must be exactly 10 digits and start with 6-9.');
                valid = false;
            }

            if (dobField) {
                const dob = new Date(dobField.value);
                const age = getAge(dobField.value);
                if (!dobField.value || dob >= new Date()) {
                    showFieldError(dobField, 'Enter a valid past date of birth.');
                    valid = false;
                } else if (age < 18) {
                    showFieldError(dobField, 'You must be at least 18 years old.');
                    valid = false;
                }
            }

            if (incomeField) {
                const raw = incomeField.value.trim();
                const n = Number(raw);
                if (!raw) {
                    showFieldError(incomeField, 'Annual income is required.');
                    valid = false;
                } else if (!Number.isFinite(n)) {
                    showFieldError(incomeField, 'Annual income must be a numeric value.');
                    valid = false;
                } else if (n < 50000) {
                    showFieldError(incomeField, 'Annual income must be at least ₹50000.');
                    valid = false;
                }
            }

            if (addressField) {
                const v = addressField.value.trim();
                if (!v) {
                    showFieldError(addressField, 'Address is required.');
                    valid = false;
                } else if (v.length < 10) {
                    showFieldError(addressField, 'Address must be at least 10 characters.');
                    valid = false;
                }
            }

            if (!valid) e.preventDefault();
        });
    }

    /* ============================================================
       6. LOGIN FORM
    ============================================================ */
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            const email = document.getElementById('email');
            const pw = document.getElementById('password');
            if (email && !email.value.includes('@')) {
                showFieldError(email, 'Please enter a valid email.');
                e.preventDefault();
            }
            if (pw && pw.value.length < 1) {
                showFieldError(pw, 'Password is required.');
                e.preventDefault();
            }
        });
    }

    /* ============================================================
       7. CONFIRMATION MODAL before form submit
    ============================================================ */
    document.querySelectorAll('[data-confirm]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const msg = this.dataset.confirm;
            if (!confirm(msg)) e.preventDefault();
        });
    });

    // Glassmorphism confirm modal (application submission)
    const applyForm = document.getElementById('applyForm');
    if (applyForm) {
        applyForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            modal.show();
        });

        const confirmSubmit = document.getElementById('confirmSubmitBtn');
        if (confirmSubmit) {
            confirmSubmit.addEventListener('click', function () {
                applyForm.removeEventListener('submit', arguments.callee);
                applyForm.submit();
            });
        }
    }

    /* ============================================================
       8. SEARCH & FILTER (Schemes)
    ============================================================ */
    const schemeSearch = document.getElementById('schemeSearch');
    const categoryFilter = document.getElementById('categoryFilter');

    if (schemeSearch || categoryFilter) {
        function filterSchemes() {
            const q = schemeSearch ? schemeSearch.value.toLowerCase() : '';
            const cat = categoryFilter ? categoryFilter.value.toLowerCase() : '';
            document.querySelectorAll('.scheme-card-wrap').forEach(function (wrap) {
                const title = wrap.dataset.title ? wrap.dataset.title.toLowerCase() : '';
                const category = wrap.dataset.category ? wrap.dataset.category.toLowerCase() : '';
                const matchQ = !q || title.includes(q);
                const matchCat = !cat || category === cat;
                wrap.style.display = (matchQ && matchCat) ? '' : 'none';
            });
        }

        if (schemeSearch) schemeSearch.addEventListener('input', filterSchemes);
        if (categoryFilter) categoryFilter.addEventListener('change', filterSchemes);
    }

    /* ============================================================
       9. ELIGIBILITY CHECKER
    ============================================================ */
    const eligibilityForm = document.getElementById('eligibilityForm');
    if (eligibilityForm) {
        eligibilityForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const age = parseInt(document.getElementById('check_age').value);
            const income = parseInt(document.getElementById('check_income').value);
            const category = document.getElementById('check_category').value;

            const minAge = parseInt(eligibilityForm.dataset.minAge || 0);
            const maxAge = parseInt(eligibilityForm.dataset.maxAge || 120);
            const maxIncome = parseInt(eligibilityForm.dataset.maxIncome || 999999);
            const eligibleCats = eligibilityForm.dataset.categories || 'All';

            const ageOk = age >= minAge && age <= maxAge;
            const incomeOk = income <= maxIncome;
            const catOk = eligibleCats.includes('All') || eligibleCats.includes(category);

            const result = document.getElementById('eligibilityResult');
            result.style.display = 'block';

            if (ageOk && incomeOk && catOk) {
                result.className = 'eligibility-result eligible';
                result.innerHTML = '<i class="fas fa-check-circle me-2 text-success"></i><strong>You are eligible!</strong> You meet all the criteria for this scheme. Click Apply Now to proceed.';
            } else {
                let reasons = [];
                if (!ageOk) reasons.push(`Age must be between ${minAge}-${maxAge} years`);
                if (!incomeOk) reasons.push(`Annual income must be below ₹${maxIncome.toLocaleString()}`);
                if (!catOk) reasons.push(`Category must be: ${eligibleCats}`);
                result.className = 'eligibility-result not-eligible';
                result.innerHTML = `<i class="fas fa-times-circle me-2 text-danger"></i><strong>Not eligible.</strong> Reasons: ${reasons.join('; ')}.`;
            }
        });
    }

    /* ============================================================
       10. PROFILE COMPLETION BAR
    ============================================================ */
    const progressBar = document.querySelector('.profile-progress-bar');
    if (progressBar) {
        const target = parseInt(progressBar.dataset.progress || 0);
        let current = 0;
        const step = target / 60;
        const interval = setInterval(function () {
            current = Math.min(current + step, target);
            progressBar.style.width = current + '%';
            const label = document.getElementById('profileProgressLabel');
            if (label) label.textContent = Math.round(current) + '%';
            if (current >= target) clearInterval(interval);
        }, 16);
    }

    /* ============================================================
       11. PASSWORD VISIBILITY TOGGLE
    ============================================================ */
    document.querySelectorAll('.password-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = this.closest('.position-relative').querySelector('input');
            if (input.type === 'password') {
                input.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                input.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });

    /* ============================================================
       12. AUTO DISMISS ALERTS
    ============================================================ */
    document.querySelectorAll('.auto-dismiss').forEach(function (alert) {
        setTimeout(function () {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    /* ============================================================
       13. COUNTER ANIMATION (analytics cards)
    ============================================================ */
    document.querySelectorAll('.counter-animate').forEach(function (el) {
        const target = parseInt(el.dataset.target || el.textContent);
        const suffix = el.dataset.suffix || '';
        let current = 0;
        const duration = 1500;
        const step = target / (duration / 16);

        const timer = setInterval(function () {
            current = Math.min(current + step, target);
            el.textContent = Math.floor(current) + suffix;
            if (current >= target) clearInterval(timer);
        }, 16);
    });

    /* ============================================================
       HELPERS
    ============================================================ */
    function showFieldError(field, message) {
        field.classList.add('is-invalid');
        // Prefer an existing invalid-feedback within the nearest form-group container,
        // otherwise create one directly after the field so it appears below the input.
        let container = field.parentElement;
        for (let i = 0; i < 3; i++) {
            if (!container) break;
            if (container.querySelector && container.querySelector('.invalid-feedback')) break;
            container = container.parentElement;
        }

        let fb = (container && container.querySelector) ? container.querySelector('.invalid-feedback') : null;
        if (!fb) {
            fb = document.createElement('div');
            fb.className = 'invalid-feedback';
            if (field.nextElementSibling) {
                field.parentNode.insertBefore(fb, field.nextElementSibling);
            } else {
                field.parentNode.appendChild(fb);
            }
        }
        fb.textContent = message;
        fb.style.display = 'block';
        fb.style.color = '#ff6b6b';
        fb.style.fontSize = '12px';
        fb.style.marginTop = '4px';
    }

    function clearFieldError(field) {
        field.classList.remove('is-invalid');
        let container = field.parentElement;
        for (let i = 0; i < 3; i++) {
            if (!container) break;
            if (container.querySelector && container.querySelector('.invalid-feedback')) break;
            container = container.parentElement;
        }
        const fb = (container && container.querySelector) ? container.querySelector('.invalid-feedback') : null;
        if (fb) fb.style.display = 'none';
    }

    function updateStrengthIndicator(pw, hasUpper, hasNum, hasLen) {
        const bar = document.getElementById('strengthBar');
        const label = document.getElementById('strengthLabel');
        if (!bar || !label) return;

        let strength = 0;
        if (hasLen) strength++;
        if (hasUpper) strength++;
        if (hasNum) strength++;
        if (pw.length >= 12) strength++;

        const levels = ['Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];
        const colors = ['#dc3545', '#dc3545', '#ffc107', '#28a745', '#20c997'];
        const widths = ['20%', '35%', '55%', '80%', '100%'];

        label.textContent = levels[strength] || 'Very Weak';
        label.style.color = colors[strength] || '#dc3545';
        bar.style.width = widths[strength] || '20%';
        bar.style.background = colors[strength] || '#dc3545';
    }

});
