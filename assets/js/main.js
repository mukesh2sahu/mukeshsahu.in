/**
 * MukeshSahu Platform - Main JavaScript
 * Handles AJAX Bookmarks, Course Lecture Progress, and UI Interactions
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Sticky Navbar scroll effect
    const navbar = document.querySelector('.navbar-custom');
    if (navbar) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 20) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // 2. Initialize Bootstrap Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 3. Password visibility toggle
    document.querySelectorAll('.btn-toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        });
    });

    // 4. AJAX Save/Bookmark Job
    document.querySelectorAll('.btn-save-job').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const jobId = this.getAttribute('data-job-id');
            const token = this.getAttribute('data-csrf');
            const icon = this.querySelector('i');
            const countSpan = document.getElementById('saved-jobs-count');

            fetch(window.APP_URL + '/modules/jobs/save-toggle.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    job_id: jobId,
                    csrf_token: token
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.is_saved) {
                        icon.classList.remove('fa-regular');
                        icon.classList.add('fa-solid');
                        this.classList.add('text-primary');
                        showToast('Job saved to your bookmarks!', 'success');
                    } else {
                        icon.classList.remove('fa-solid');
                        icon.classList.add('fa-regular');
                        this.classList.remove('text-primary');
                        showToast('Job removed from your bookmarks.', 'info');
                    }
                    if (countSpan && data.total_saved !== undefined) {
                        countSpan.textContent = data.total_saved;
                    }
                } else {
                    showToast(data.message || 'Error updating saved job.', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Something went wrong. Please try again.', 'danger');
            });
        });
    });

    // 5. AJAX Save/Bookmark AI Tool
    document.querySelectorAll('.btn-save-tool').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const toolId = this.getAttribute('data-tool-id');
            const token = this.getAttribute('data-csrf');
            const icon = this.querySelector('i');

            fetch(window.APP_URL + '/modules/ai-tools/save-toggle.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    tool_id: toolId,
                    csrf_token: token
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.is_saved) {
                        icon.classList.remove('fa-regular');
                        icon.classList.add('fa-solid');
                        this.classList.add('text-danger');
                        showToast('AI Tool added to favorites!', 'success');
                    } else {
                        icon.classList.remove('fa-solid');
                        icon.classList.add('fa-regular');
                        this.classList.remove('text-danger');
                        showToast('AI Tool removed from favorites.', 'info');
                    }
                } else {
                    showToast(data.message || 'Error updating saved tool.', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Something went wrong. Please try again.', 'danger');
            });
        });
    });

    // 6. Course Lecture Progress Toggle (Mark as Completed)
    document.querySelectorAll('.btn-toggle-progress').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const courseId = this.getAttribute('data-course-id');
            const lectureId = this.getAttribute('data-lecture-id');
            const token = this.getAttribute('data-csrf');
            const icon = this.querySelector('i');
            const btnText = this.querySelector('.btn-label');
            const progressBar = document.getElementById('course-progress-bar');
            const progressText = document.getElementById('course-progress-text');

            fetch(window.APP_URL + '/modules/courses/toggle-progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    course_id: courseId,
                    lecture_id: lectureId,
                    csrf_token: token
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.is_completed) {
                        this.classList.remove('btn-outline-success');
                        this.classList.add('btn-success');
                        if (icon) {
                            icon.classList.remove('fa-circle-check');
                            icon.classList.add('fa-check-double');
                        }
                        if (btnText) btnText.textContent = 'Completed';
                        showToast('Lecture marked as completed!', 'success');
                    } else {
                        this.classList.remove('btn-success');
                        this.classList.add('btn-outline-success');
                        if (icon) {
                            icon.classList.remove('fa-check-double');
                            icon.classList.add('fa-circle-check');
                        }
                        if (btnText) btnText.textContent = 'Mark as Completed';
                        showToast('Lecture marked as incomplete.', 'info');
                    }

                    // Update Progress Bar
                    if (progressBar) {
                        progressBar.style.width = data.progress_percentage + '%';
                        progressBar.setAttribute('aria-valuenow', data.progress_percentage);
                    }
                    if (progressText) {
                        progressText.textContent = Math.round(data.progress_percentage) + '%';
                    }

                    // Update corresponding item in lecture list
                    const listIcon = document.getElementById('lecture-icon-' + lectureId);
                    if (listIcon) {
                        if (data.is_completed) {
                            listIcon.className = 'fa-solid fa-circle-check text-success me-2';
                        } else {
                            listIcon.className = 'fa-regular fa-circle-play text-muted me-2';
                        }
                    }
                } else {
                    showToast(data.message || 'Error updating lecture progress.', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Network error while saving progress.', 'danger');
            });
        });
    });

    // Simple toast notification function
    window.showToast = function (message, type = 'info') {
        let container = document.getElementById('global-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'global-toast-container';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }

        const toastId = 'toast_' + Date.now();
        const bgClass = type === 'danger' ? 'bg-danger text-white' : (type === 'success' ? 'bg-success text-white' : 'bg-dark text-white');
        
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center ${bgClass} border-0 shadow`;
        toastEl.id = toastId;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body py-2 px-3 fw-semibold">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        container.appendChild(toastEl);
        const bsToast = new bootstrap.Toast(toastEl, { delay: 3500 });
        bsToast.show();

        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    };
});
