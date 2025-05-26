// Font configurations
const chartFont = {
  family: "'Inter', sans-serif",
  size: 14,
  weight: 'normal',
  color: '#111827'
};

const darkChartFont = {
  ...chartFont,
  color: '#f9fafb'
};

let pieChart = null;
let barChart = null;

document.addEventListener("DOMContentLoaded", async function () {
  
  const response = await fetchReviewStats();
  if (response) {
    renderCharts(response.data.star_counts);
  } else {
    console.warn("No starCounts available to render charts.");
  }

  // Theme toggle support
  document.getElementById('themeToggle').addEventListener('click', function () {
    setTimeout(() => {
      const isDark = document.body.classList.contains('dark');
      const fontConfig = isDark ? darkChartFont : chartFont;

      if (pieChart && barChart) {
        pieChart.options.plugins.legend.labels.font = fontConfig;
        pieChart.options.plugins.tooltip.bodyFont = fontConfig;
        pieChart.options.plugins.tooltip.titleFont = fontConfig;

        barChart.options.plugins.legend.labels.font = fontConfig;
        barChart.options.plugins.tooltip.bodyFont = fontConfig;
        barChart.options.plugins.tooltip.titleFont = fontConfig;
        barChart.options.scales.x.ticks.font = fontConfig;
        barChart.options.scales.x.title.font = fontConfig;
        barChart.options.scales.y.ticks.font = fontConfig;
        barChart.options.scales.y.title.font = fontConfig;

        pieChart.update();
        barChart.update();
      }
    }, 100);
  });
});


async function fetchReviewStats() {
  const apikey = localStorage.getItem(apikey);
  if(!apikey)
  {
    alert("Session expired. Please log in again.");
    popup.remove();
    return;
  }
  const username = WHEATLEY_USERNAME;
  const password = WHEATLEY_PASSWORD;

  const payload = {
    type: "getReviewStats",
    apikey: apikey
  };

  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'https://wheatley.cs.up.ac.za/u24634434/COS221/api.php', true);
  xhr.setRequestHeader("Content-Type", "application/json");
  xhr.setRequestHeader("Authorization", "Basic " + btoa(username + ":" + password));

  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4) {
      if (xhr.status === 200) {
        try {
          const response = JSON.parse(xhr.responseText);
          const starCounts = response.data.star_counts;

          if (!starCounts) {
            console.warn("No starCounts available to render charts.");
            return;
          }

          console.log("Fetched starCounts:", starCounts);
          renderCharts(starCounts);
          updateAggregateTile({
          totalReviews: response.data.total_reviews,
          averageRating: response.data.average_review,
          highestRating: getHighestRating(response.data.star_counts),
          lowestRating: getLowestRating(response.data.star_counts)
        });
        } catch (err) {
          console.error("Error parsing JSON response:", err);
        }
      } else {
        console.error("Failed request: " + xhr.status + " " + xhr.statusText);
      }
    }
  };

  xhr.send(JSON.stringify(payload));
}


function renderCharts(data) {
  const labels = Object.keys(data);
  const starData = Object.values(data);
  const total = starData.reduce((a, b) => a + b, 0);
  const pieData = starData.map(val => (val / total) * 100);

  // Pie chart
  const pieCtx = document.getElementById('pieChart').getContext('2d');
  pieChart = new Chart(pieCtx, {
    type: 'pie',
    data: {
      labels: labels,
      datasets: [{
        data: pieData,
        backgroundColor: ['#FF6384', '#FF9F40', '#FFCD56', '#4BC0C0', '#36A2EB'],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'right',
          labels: { font: chartFont, padding: 20 }
        },
        tooltip: {
          bodyFont: chartFont,
          titleFont: chartFont,
          callbacks: {
            label: function (context) {
              const label = context.label || '';
              const value = context.raw || 0;
              return `${label}: ${value.toFixed(1)}%`;
            }
          }
        }
      }
    }
  });

  // Bar chart
  const barCtx = document.getElementById('barChart').getContext('2d');
  barChart = new Chart(barCtx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Number of Reviews',
        data: starData,
        backgroundColor: '#36A2EB',
        borderColor: '#2980B9',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          labels: { font: chartFont }
        },
        tooltip: {
          bodyFont: chartFont,
          titleFont: chartFont,
          callbacks: {
            label: function (context) {
              const label = context.dataset.label || '';
              const value = context.raw || 0;
              const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
              const percentage = Math.round((value / total) * 100);
              return `${label}: ${value} (${percentage}%)`;
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Number of Reviews',
            font: chartFont
          },
          ticks: { font: chartFont }
        },
        x: {
          title: {
            display: true,
            text: 'Rating',
            font: chartFont
          },
          ticks: { font: chartFont }
        }
      }
    }
  });
}

function getHighestRating(starCounts) {
  for (let i = 5; i >= 1; i--) {
    if (starCounts[i.toString()] > 0) return i;
  }
  return 0;
}

function getLowestRating(starCounts) {
  for (let i = 1; i <= 5; i++) {
    if (starCounts[i.toString()] > 0) return i;
  }
  return 0;
}


function updateAggregateTile({ totalReviews, averageRating, highestRating, lowestRating }) {
  document.querySelector('.aggregate-number .big-number').textContent = totalReviews;
  document.querySelector('.rating-text .rating-value').textContent = averageRating.toFixed(1);

  updateStars('.aggregate-rating .stars', averageRating);
  updateStars('.range-item:nth-child(1) .stars', highestRating);

  document.querySelector('.range-item:nth-child(1) .rating-value').textContent = highestRating.toFixed(1);

  updateStars('.range-item:nth-child(2) .stars', lowestRating);

  document.querySelector('.range-item:nth-child(2) .rating-value').textContent = lowestRating.toFixed(1);
}


function updateStars(selector, rating) {
  const container = document.querySelector(selector);
  container.innerHTML = ''; 

  const fullStars = Math.floor(rating);
  const hasHalfStar = rating % 1 >= 0.25 && rating % 1 < 0.75;
  const remainingStars = 5 - fullStars - (hasHalfStar ? 1 : 0);

  for (let i = 0; i < fullStars; i++) 
  {
    container.innerHTML += `<span class="material-symbols-outlined">star</span>`;
  }

  if (hasHalfStar) 
  {
    container.innerHTML += `<span class="material-symbols-outlined">star_half</span>`;
  }

  for (let i = 0; i < remainingStars; i++) 
  {
    container.innerHTML += `<span class="material-symbols-outlined">grade</span>`;
  }
}
