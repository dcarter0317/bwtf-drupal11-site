
// MENU
(function() {
  'use strict';
  
  
  function initMenu() {
    
    const menuBtn = document.querySelector('.menu-btn');
    const navList = document.querySelector('.nav');

    if (menuBtn && navList) {
      
      // Try both the container and the button itself
      menuBtn.onclick = function(e) {
        menuBtn.classList.toggle('open');
        navList.classList.toggle('nav-open');
        return false;
      };
      
    } else {
      console.error('Elements not found!', { menuBtn, navList });
    }
  }
  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMenu);
  } else {
    initMenu();
  }
})();