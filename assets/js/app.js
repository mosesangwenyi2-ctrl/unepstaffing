// Minimal JS file for future enhancements
document.addEventListener('DOMContentLoaded', function () {
    // See More / Show Less toggle functionality
    const toggleButtons = document.querySelectorAll('[data-toggle-list]');
    
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const listId = this.getAttribute('data-toggle-list');
            const hiddenItems = document.querySelectorAll(`[data-list-id="${listId}"].list-item-hidden`);
            const isExpanded = this.getAttribute('data-expanded') === 'true';
            
            hiddenItems.forEach(item => {
                if (isExpanded) {
                    item.classList.add('list-item-hidden');
                } else {
                    item.classList.remove('list-item-hidden');
                }
            });
            
            // Toggle button text and state
            this.setAttribute('data-expanded', !isExpanded);
            if (isExpanded) {
                this.innerHTML = '<i class="bi bi-chevron-down"></i> See More';
            } else {
                this.innerHTML = '<i class="bi bi-chevron-up"></i> Show Less';
            }
        });
    });
