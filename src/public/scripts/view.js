document.addEventListener("DOMContentLoaded", function () {
    fetchProductDetails();
});

function fetchProductDetails() {
    const apKey = "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef"; //getCookie("apiKey"); 
    const productID = 1;

    const payload = {
        type: "getProductDetails",
        apikey: apKey,
        product_id: productID
    };

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
                    let jsonStartIdx = responseText.indexOf('{');
                    if (jsonStartIdx >= 0) responseText = responseText.substring(jsonStartIdx);

                    const response = JSON.parse(responseText);

                    if (response.status === 'success') {
                        populateProductView(response.data);
                    } else {
                        alert('Failed to load product: ' + (response.message || 'Unknown error'));
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

function populateProductView(data) {
    // Populate main product info
   // console.log(data);
    document.getElementById("productName").textContent = data.name;
    document.getElementById("productDescription").textContent = data.description;
    document.getElementById("productImage").src = data.Image_url;
   // document.getElementById("productCategory").textContent = data.category;
    document.getElementById("productAvgReview").textContent = data.average_review ?? "N/A";
    document.getElementById("productCheapestPrice").textContent = data.cheapest_price 
        ? `R${data.cheapest_price.toFixed(2)} from ${data.cheapest_retailer}` 
        : "Price not available";

    renderRetailerPrices(data.retailers);
    updateReviewsSection(data); 
}

function renderRetailerPrices(retailers) {
    const container = document.getElementById("retailerPricesContainer");
    container.innerHTML = ''; // Clear existing

    retailers.forEach(({ retailer_name, price }) => {
        const box = document.createElement("div");
        box.className = "retailer-box";
        box.innerHTML = `
            <div class="retailer-name">${retailer_name}</div>
            <div class="retailer-price">R${price.toFixed(2)}</div>
            <button class="buy-now-btn">Buy Now</button>
        `;
        container.appendChild(box);
    });
}

function updateReviewsSection(productData) {
  const { average_review, reviews } = productData;

  document.getElementById("productAvgReview").textContent = `(${average_review?.toFixed(1) || '0.0'})`;
  document.querySelector(".review-count").textContent = `${reviews.length} reviews`;
  document.querySelector(".rating-number").textContent = average_review?.toFixed(1) || '0.0';

  const starContainer = document.querySelector(".star-rating");
  starContainer.querySelectorAll(".material-symbols-outlined").forEach(s => s.remove());

  const fullStars = Math.floor(average_review || 0);
  const halfStar = (average_review || 0) % 1 >= 0.5;

  for (let i = 0; i < fullStars; i++) {
    const star = document.createElement("span");
    star.className = "material-symbols-outlined";
    star.textContent = "star";
    starContainer.insertBefore(star, starContainer.children[i]);
  }

  if (halfStar) {
    const half = document.createElement("span");
    half.className = "material-symbols-outlined";
    half.textContent = "star_half";
    starContainer.insertBefore(half, starContainer.children[fullStars]);
  }

  const distribution = [0, 0, 0, 0, 0]; // index 0 = 1 star, index 4 = 5 stars
  reviews.forEach(({ score }) => {
    if (score >= 1 && score <= 5) distribution[score - 1]++;
  });
  const total = reviews.length || 1;

  const bars = document.querySelectorAll(".rating-bar");
  for (let i = 5; i >= 1; i--) {
    const count = distribution[i - 1];
    const percentage = (count / total * 100).toFixed(1);
    const bar = bars[5 - i]; 

    bar.querySelector(".bar").style.width = `${percentage}%`;
    bar.querySelector("span:last-child").textContent = count;
  }

  const container = document.getElementById("userReviewsContainer");
  container.innerHTML = "";

  reviews.forEach(({ customer_name, score, description, updated_at }) => {
    const review = document.createElement("div");
    review.className = "review";

    const stars = Array.from({ length: 5 }, (_, i) =>
      `<span class="material-symbols-outlined">${i < score ? 'star' : 'star_border'}</span>`
    ).join("");

    const reviewDate = new Date(updated_at).toLocaleDateString(undefined, {
      year: "numeric", month: "long", day: "numeric"
    });

    review.innerHTML = `
      <div class="review-header">
        <div class="review-stars">${stars}</div>
        <div class="reviewer-name">${customer_name}</div>
        <div class="review-date">Reviewed on ${reviewDate}</div>
      </div>
      <div class="review-content">${description}</div>
    `;
    container.appendChild(review);
  });
}

function getCookie(name) {
    const cname = name + "=";
    const decodedCookie = decodeURIComponent(document.cookie);
    const ca = decodedCookie.split(';');
    for (let c of ca) {
        c = c.trim();
        if (c.indexOf(cname) === 0) return c.substring(cname.length);
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

function writeReview()
{
    const payload = {
        type: "writeReview",
        apikey: "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef", //getCookie("apiKey"); 
        product_id: 1,
        score: 4,
        description: "Defnitely a product its nice"
    }

    sendReview(payload);
}

function viewMoreReviews()
{
    alert("Yeah nah, ur done");
}