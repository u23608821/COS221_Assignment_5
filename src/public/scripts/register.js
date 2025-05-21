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

function applySavedTheme() {
    const savedTheme = getCookie("theme");
    if (savedTheme === "dark") {
        document.body.classList.add("dark");
    } else {
        document.body.classList.remove("dark");
    }
}

function buildPasswordRegex({ minLength = 8, upper = true, lower = true, digit = true, special = true } = {}) {
    let pattern = "^";
    if (lower) pattern += "(?=.*[a-z])";
    if (upper) pattern += "(?=.*[A-Z])";
    if (digit) pattern += "(?=.*\\d)";
    if (special) pattern += "(?=.*[\\W_])";
    pattern += `.{${minLength
        },}$`;
    return new RegExp(pattern);
}

window.addEventListener("load", () => {
    applySavedTheme();

    const form = document.querySelector("form");
    if (!form) return;

    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        // Get full name and split into name and surname
        const fullName = document.getElementById("name")?.value.trim() || "";
        const nameParts = fullName.split(" ").filter(part => part.length > 0);

        if (nameParts.length < 2) {
            alert("Please enter both your first name and surname.");
            return;
        }

        const surname = nameParts.pop(); // Last word is the surname
        const name = nameParts.join(" "); // Everything else is the first name

        const email = document.getElementById("email")?.value.trim() || "";
        const password = document.getElementById("password")?.value.trim() || "";
        const user_type = document.getElementById("accountType")?.value.trim() || "";

        if (!fullName) {
            alert("Please fill in your full name.");
            return;
        }
        if (!email) {
            alert("Please fill in your email.");
            return;
        }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert("Invalid email format!");
            return;
        }
        if (!password) {
            alert("Please create a password.");
            return;
        }
        const passwordRequirements = {
            minLength: 8,
            upper: true,
            lower: true,
            digit: true,
            special: true
        };
        const passwordRegex = buildPasswordRegex(passwordRequirements);
        if (!passwordRegex.test(password)) {
            alert("Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.");
            return;
        }

        if (!user_type) {
            alert("Please select a user type.");
            return;
        }

        // Set required fields and null for optional fields
        const payload = {
            type: "Register",
            name,
            surname,
            phone_number: null,
            email,
            password,
            street_number: null,
            street_name: null,
            suburb: null,
            city: null,
            zip_code: null,
            user_type
        };

        console.log('Sending registration request:', payload);
        const xhr = new XMLHttpRequest();

        xhr.open('POST', 'https://wheatley.cs.up.ac.za/u24634434/COS221/api.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    console.log('Registration response:', result);

                    if (result.status === 'success') {
                        alert("Registration successful! You can now log in.");
                        window.location.href = "login.html";
                    } else if (result.status === 'emailError') {
                        alert("Invalid email format. Please check your email address.");
                    } else if (result.status === 'passwordError') {
                        alert("Password does not meet requirements. Password must:\n- Be at least 8 characters long\n- Include uppercase and lowercase letters\n- Include numbers\n- Include special characters (#?!@$%^&*-)");
                    } else {
                        alert(result.message || "Registration failed.");
                    }
                } catch (e) {
                    console.error('Error parsing response:', xhr.responseText);
                    alert("An error occurred while processing the server response.");
                }
            } else {
                console.error('Server response:', xhr.status, xhr.responseText);
                if (xhr.status === 401) {
                    alert("Authentication error. Please check your credentials or try again later.");
                } else {
                    alert("Server error: " + xhr.status + ". Please try again later.");
                }
            }
        };

        xhr.onerror = function () {
            console.error('Request failed');
            alert("Could not connect to the server. Please check your connection and try again.");
        };

        // Send the request
        xhr.send(JSON.stringify(payload));
    });
});