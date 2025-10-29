/**
 * Shelter Management System - JavaScript Utilities
 * Minimal JS for AJAX, UI interactions, and form handling
 */

// AJAX Helper Functions
const Ajax = {
    /**
     * Send GET request
     */
    get: function(url, callback, errorCallback) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    callback(data);
                } catch (e) {
                    callback(xhr.responseText);
                }
            } else {
                if (errorCallback) {
                    errorCallback(xhr.status, xhr.responseText);
                }
            }
        };
        
        xhr.onerror = function() {
            if (errorCallback) {
                errorCallback(0, 'Network error');
            }
        };
        
        xhr.send();
    },
    
    /**
     * Send POST request
     */
    post: function(url, data, callback, errorCallback) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const responseData = JSON.parse(xhr.responseText);
                    callback(responseData);
                } catch (e) {
                    callback(xhr.responseText);
                }
            } else {
                if (errorCallback) {
                    errorCallback(xhr.status, xhr.responseText);
                }
            }
        };
        
        xhr.onerror = function() {
            if (errorCallback) {
                errorCallback(0, 'Network error');
            }
        };
        
        // Convert data object to URL-encoded string
        const params = Object.keys(data)
            .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(data[key]))
            .join('&');
        
        xhr.send(params);
    },
    
    /**
     * Send form data with file upload support
     */
    postForm: function(url, formData, callback, errorCallback) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    callback(data);
                } catch (e) {
                    callback(xhr.responseText);
                }
            } else {
                if (errorCallback) {
                    errorCallback(xhr.status, xhr.responseText);
                }
            }
        };
        
        xhr.onerror = function() {
            if (errorCallback) {
                errorCallback(0, 'Network error');
            }
        };
        
        xhr.send(formData);
    }
};

// UI Helper Functions
const UI = {
    /**
     * Show alert message
     */
    showAlert: function(message, type, container) {
        type = type || 'info';
        container = container || document.body;
        
        const alert = document.createElement('div');
        alert.className = 'alert alert-' + type;
        alert.textContent = message;
        
        if (typeof container === 'string') {
            container = document.querySelector(container);
        }
        
        container.insertBefore(alert, container.firstChild);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 5000);
    },
    
    /**
     * Show loading spinner
     */
    showLoading: function(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        const spinner = document.createElement('div');
        spinner.className = 'spinner';
        spinner.id = 'loading-spinner';
        element.appendChild(spinner);
    },
    
    /**
     * Hide loading spinner
     */
    hideLoading: function() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.remove();
        }
    },
    
    /**
     * Toggle modal
     */
    toggleModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.toggle('active');
        }
    },
    
    /**
     * Close modal
     */
    closeModal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    },
    
    /**
     * Toggle sidebar (mobile)
     */
    toggleSidebar: function() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.classList.toggle('active');
        }
    },
    
    /**
     * Toggle navbar menu (mobile)
     */
    toggleNavMenu: function() {
        const menu = document.querySelector('.navbar-menu');
        if (menu) {
            menu.classList.toggle('active');
        }
    }
};

// Form Validation
const Validator = {
    /**
     * Validate email
     */
    email: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    /**
     * Validate phone number
     */
    phone: function(phone) {
        const re = /^[\d\s\-\+\(\)]+$/;
        return re.test(phone) && phone.replace(/\D/g, '').length >= 10;
    },
    
    /**
     * Validate required field
     */
    required: function(value) {
        return value !== null && value !== undefined && value.trim() !== '';
    },
    
    /**
     * Validate minimum length
     */
    minLength: function(value, min) {
        return value.length >= min;
    },
    
    /**
     * Validate form
     */
    validateForm: function(formId) {
        const form = document.getElementById(formId);
        if (!form) return false;
        
        const inputs = form.querySelectorAll('[required]');
        let isValid = true;
        
        inputs.forEach(function(input) {
            if (!Validator.required(input.value)) {
                input.style.borderColor = '#ef4444';
                isValid = false;
            } else {
                input.style.borderColor = '';
            }
        });
        
        return isValid;
    }
};

// Theme Management
const ThemeManager = {
    /**
     * Apply user theme settings
     */
    applyTheme: function(settings) {
        const root = document.documentElement;
        
        if (settings.theme_color) {
            root.style.setProperty('--primary-color', settings.theme_color);
        }
        
        if (settings.navbar_color) {
            root.style.setProperty('--navbar-color', settings.navbar_color);
        }
        
        if (settings.button_color) {
            root.style.setProperty('--secondary-color', settings.button_color);
        }
        
        if (settings.font_size) {
            const sizes = {
                'small': '14px',
                'medium': '16px',
                'large': '18px'
            };
            root.style.setProperty('--font-size-base', sizes[settings.font_size]);
        }
        
        if (settings.custom_css) {
            const styleElement = document.createElement('style');
            styleElement.textContent = settings.custom_css;
            document.head.appendChild(styleElement);
        }
    },
    
    /**
     * Load user theme from API
     */
    loadUserTheme: function(userId) {
        Ajax.get('/api/user-settings.php?user_id=' + userId, function(response) {
            if (response.success && response.settings) {
                ThemeManager.applyTheme(response.settings);
            }
        });
    }
};

// Utility Functions
const Utils = {
    /**
     * Format date
     */
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    },
    
    /**
     * Debounce function
     */
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction() {
            const context = this;
            const args = arguments;
            
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    },
    
    /**
     * Get query parameter
     */
    getParam: function(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    },
    
    /**
     * Escape HTML
     */
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Auto-save functionality for forms
const AutoSave = {
    timers: {},
    
    /**
     * Enable auto-save for a form
     */
    enable: function(formId, saveUrl, interval) {
        interval = interval || 30000; // Default 30 seconds
        
        const form = document.getElementById(formId);
        if (!form) return;
        
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(function(input) {
            input.addEventListener('input', function() {
                clearTimeout(AutoSave.timers[formId]);
                
                AutoSave.timers[formId] = setTimeout(function() {
                    const formData = new FormData(form);
                    
                    Ajax.postForm(saveUrl, formData, function(response) {
                        if (response.success) {
                            UI.showAlert('Auto-saved', 'success', form);
                        }
                    });
                }, interval);
            });
        });
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });
    
    // Handle form submissions with AJAX
    document.querySelectorAll('form[data-ajax="true"]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!Validator.validateForm(form.id)) {
                UI.showAlert('Please fill in all required fields', 'danger', form);
                return;
            }
            
            const formData = new FormData(form);
            const url = form.action;
            
            UI.showLoading(form);
            
            Ajax.postForm(url, formData, function(response) {
                UI.hideLoading();
                
                if (response.success) {
                    UI.showAlert(response.message || 'Success', 'success', form);
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1000);
                    }
                } else {
                    UI.showAlert(response.message || 'An error occurred', 'danger', form);
                }
            }, function(status, error) {
                UI.hideLoading();
                UI.showAlert('Network error. Please try again.', 'danger', form);
            });
        });
    });
});
