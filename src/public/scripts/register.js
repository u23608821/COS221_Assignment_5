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
    pattern += `.{${
        minLength
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
        const [name, ...surnameParts] = fullName.split(" ");
        const surname = surnameParts.join(" ");
        const email = document.getElementById("email")?.value.trim() || "";
        const password = document.getElementById("password")?.value.trim() || "";
        const user_type = document.getElementById("accountType")?.value.trim() || "";

        // Basic validation
        if (!fullName){
            alert("Please fill in your full name.");
            return;
        }
        if (!email) {
            alert("Please fill in your email.");
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

        const payload = {
            type: "Register",
            name,
            surname,
            phone_number,
            email,
            password,
            street_number,
            street_name,
            suburb,
            city,
            zip_code,
            user_type
        };

        try {
            const response = await fetch("/api.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (result.status === "success") {
                alert("Registration successful! You can now log in.");
                window.location.href = "/login.html";
            } else {
                alert(result.message || "Registration failed.");
            }
        } catch (err) {
            alert("An error occurred during registration. Please try again.");
        }
    });
});