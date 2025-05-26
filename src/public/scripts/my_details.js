document.addEventListener('DOMContentLoaded', function() {
    // Initialize DOM elements
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
    accountMenu.classList.toggle("display");
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
}); // End of DOMContentLoaded

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


// Functionality
document.addEventListener('DOMContentLoaded', function () {
    loadUserDetails();

    const form = document.querySelector('.details-form form');
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        saveUserDetails();
    });
});

function loadUserDetails() {
    // Create a new XMLHttpRequest object
    const xhr = new XMLHttpRequest();

    // Configure the request
    xhr.open('POST', 'https://wheatley.cs.up.ac.za/u24634434/COS221/api.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    const data = {
        type: "ViewCustomer",
        email: getCookie("email"),
        apiKey: getCookie("apiKey")
    };

    xhr.onload = function () {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.status === 'success') {
                // Populate form fields with user data
                document.getElementById('firstName').value = response.data.name || '';
                document.getElementById('lastName').value = response.data.surname || '';
                document.getElementById('phone').value = response.data.phone_number || '';
                document.getElementById('email').value = response.data.email || '';
                document.getElementById('streetNumber').value = response.data.street_number || '';
                document.getElementById('streetName').value = response.data.street_name || '';
                document.getElementById('suburb').value = response.data.suburb || '';
                document.getElementById('city').value = response.data.city || '';
                document.getElementById('postalCode').value = response.data.zip_code || '';
            } else {
                alert('Error loading user details: ' + response.message);
            }
        }
    };

    xhr.onerror = function () {
        alert('Error loading user details');
    };

    xhr.send(JSON.stringify(data));
}

function saveUserDetails() {
    
    const xhr = new XMLHttpRequest();

   
    xhr.open('POST', 'https://wheatley.cs.up.ac.za/u24634434/COS221/api.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    const data = {
        type: "UpdateCustomer",
        email: getCookie("email"),
        apiKey: getCookie("apiKey"),
        name: document.getElementById('firstName').value,
        surname: document.getElementById('lastName').value,
        phone_number: document.getElementById('phone').value,
        street_number: document.getElementById('streetNumber').value,
        street_name: document.getElementById('streetName').value,
        suburb: document.getElementById('suburb').value,
        city: document.getElementById('city').value,
        zip_code: document.getElementById('postalCode').value
    };

    xhr.onload = function () {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.status === 'success') {
                alert('Details updated successfully!');
            } else {
                alert('Error updating details: ' + response.message);
            }
        }
    };

    xhr.onerror = function () {
        alert('Error updating details');
    };

    xhr.send(JSON.stringify(data));
}