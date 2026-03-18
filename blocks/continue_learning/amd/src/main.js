define([], function() {
    "use strict";

    return {
        init: function() {
            const cards = document.querySelectorAll('.block-continue-learning .continue-learning-card');
            cards.forEach((card) => {
                card.addEventListener('mouseenter', () => card.classList.add('is-hover'));
                card.addEventListener('mouseleave', () => card.classList.remove('is-hover'));
            });
        }
    };
});
