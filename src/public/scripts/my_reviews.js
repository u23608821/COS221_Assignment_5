
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

function sendReview(payload)
{

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'https://wheatley.cs.up.ac.za/u24634434/COS221/api.php', true);
 //  xhr.open('POST', 'http://localhost:8000/api.php')
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.setRequestHeader("Authorization", "Basic " + btoa(WHEATLEY_USERNAME + ":" + WHEATLEY_PASSWORD));

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    let responseText = xhr.responseText;
                    const response = JSON.parse(responseText);

                    if (response.status === 'success') {
                        alert("Wrote a review: " + (response.message || "Unkown error"));
                    } else {
                        alert('Failed to review product: ' + (response.message || 'Unknown error'));
                    }
                } catch (e) {
                    console.error("Error parsing response:", e, xhr.responseText);
                    alert("Error processing response from server.");
                }
            } else {
                console.error("Server returned error status:", xhr.status);
                alert("Server error: " + xhr.status);
            }
        }
    };

    xhr.onerror = function (e) {
        console.error('Network Error', e);
        alert('Network Error: Could not connect to the server');
    };

    xhr.send(JSON.stringify(payload));
}

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.review-box').forEach(function (box, idx) {
        const deleteBtn = box.querySelector('.delete-btn');
        
        deleteBtn.addEventListener('click', function () {
            // Create popup
            let popup = document.createElement('div');
            popup.className = "delete-popup";
            popup.style.position = "fixed";
            popup.style.top = "0";
            popup.style.left = "0";
            popup.style.width = "100vw";
            popup.style.height = "100vh";
            popup.style.background = "rgba(0,0,0,0.4)";
            popup.style.display = "flex";
            popup.style.alignItems = "center";
            popup.style.justifyContent = "center";
            popup.style.zIndex = "9999";
            popup.innerHTML = `
                <div style="background:#fff;padding:2em;border-radius:8px;box-shadow:0 2px 8px #0003;text-align:center;">
                    <p>Are you sure you want to delete your review?</p>
                    <button class="popup-yes">Yes</button>
                    <button class="popup-no">No</button>
                </div>
            `;
            document.body.appendChild(popup);

            popup.querySelector('.popup-no').onclick = function () {
                popup.remove();
            };
            
            popup.querySelector('.popup-yes').onclick = function () {
                // Changed from getCookie to localStorage
                const apiKey = localStorage.getItem('apiKey');
                if (!apiKey) {
                    alert('Session expired. Please log in again.');
                    popup.remove();
                    return;
                }
                
                const product_id = 1 + idx;
                const payload = {
                    type: "deleteReview",
                    apiKey: apiKey,  // Now using localStorage consistently
                    product_id: product_id
                };
                sendReview(payload);

                box.remove();
                popup.remove();
            };
        });
    });
});