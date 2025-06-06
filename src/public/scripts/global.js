const accountBtn = document.getElementById("accountBtn");
const accountMenu = document.getElementById("accountMenu");
const themeToggle = document.getElementById("themeToggle");
const themeIcon = document.getElementById("themeIcon");
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");

function updateIcon() {
    themeIcon.textContent = document.body.classList.contains("dark") ? "light_mode" : "dark_mode";
}

function applySavedTheme() {
    const savedTheme = getCookie("theme");
    if (savedTheme === "dark") {
        document.body.classList.add("dark");
    } else {
        document.body.classList.remove("dark");
    }
    updateIcon();
}

window.addEventListener("load", applySavedTheme);

accountBtn.addEventListener("click", function () {
    accountMenu.classList.toggle("show");
});

themeToggle.addEventListener("click", function () {
    document.body.classList.toggle("dark");
    const newTheme = document.body.classList.contains("dark") ? "dark" : "light";
    setCookie("theme", newTheme, 30);
    updateIcon();
});

menuToggle.addEventListener("click", function () {
    navLinks.classList.toggle("show");
});

window.addEventListener("click", function (e) {
    if (!accountBtn.contains(e.target) && !accountMenu.contains(e.target)) {
        accountMenu.classList.remove("show");
    }
});

updateIcon();

function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
    let expires = "expires=" + d.toUTCString();
    document.cookie = name + "=" + value + ";" + expires + ";path=/";
}

function getCookie(name) {
    let cname = name + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i].trim();
        if (c.indexOf(cname) === 0) {
            return c.substring(cname.length, c.length);
        }
    }
    return "";
}

function handleLogout() {
    // Clear all relevant local storage items
    localStorage.removeItem('apiKey');
    localStorage.removeItem('name');
    localStorage.removeItem('user_type'); 
    // Redirect to login page
    window.location.href = 'https://wheatley.cs.up.ac.za/u24634434/COS221/src/public/html/login.php';
}

document.addEventListener('DOMContentLoaded', function() {
    applySavedTheme();
    updateUserGreeting(); // Ensure this runs on page load
    
    // Check if user is logged in!
    const apiKey = localStorage.getItem('apiKey');
    if (!apiKey && !window.location.pathname.includes('login.php')) {
        window.location.href = 'https://wheatley.cs.up.ac.za/u24634434/COS221/src/public/index.html';
    }

    // Add click handler to all sign out links
    const signOutLinks = document.querySelectorAll('.signout');
    signOutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            handleLogout();
        });
    });

    
});


function updateUserGreeting() {
    const firstName = localStorage.getItem('name'); // Changed from 'first_name' to 'name'
    const userTextElement = document.querySelector('.user-text');
    
    if (userTextElement) {
        if (firstName) {
            userTextElement.textContent = `${firstName}`;
        } else {
            userTextElement.textContent = 'User';
        }
    }
}