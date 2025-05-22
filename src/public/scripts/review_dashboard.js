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

// Initialise charts
document.addEventListener('DOMContentLoaded', function() {
  // Pie Chart
  const pieCtx = document.getElementById('pieChart').getContext('2d');
  const pieChart = new Chart(pieCtx, {
    type: 'pie',
    data: {
      labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
      datasets: [{
        data: [15, 29, 42, 87, 156],
        backgroundColor: [
          '#FF6384',
          '#FF9F40',
          '#FFCD56',
          '#4BC0C0',
          '#36A2EB'
        ],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'right',
          labels: {
            font: chartFont,
            padding: 20
          }
        },
        tooltip: {
          bodyFont: chartFont,
          titleFont: chartFont,
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              const value = context.raw || 0;
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = Math.round((value / total) * 100);
              return `${label}: ${value} (${percentage}%)`;
            }
          }
        }
      }
    }
  });

  // Bar Chart
  const barCtx = document.getElementById('barChart').getContext('2d');
  const barChart = new Chart(barCtx, {
    type: 'bar',
    data: {
      labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
      datasets: [{
        label: 'Number of Reviews',
        data: [15, 29, 42, 87, 156],
        backgroundColor: '#36A2EB',
        borderColor: '#2980B9',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          labels: {
            font: chartFont
          }
        },
        tooltip: {
          bodyFont: chartFont,
          titleFont: chartFont,
          callbacks: {
            label: function(context) {
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
          ticks: {
            font: chartFont
          }
        },
        x: {
          title: {
            display: true,
            text: 'Rating',
            font: chartFont
          },
          ticks: {
            font: chartFont
          }
        }
      }
    }
  });

  // Theme toggle handler
  document.getElementById('themeToggle').addEventListener('click', function() {
    setTimeout(() => {
      const isDark = document.body.classList.contains('dark');
      const fontConfig = isDark ? darkChartFont : chartFont;
      
      // Update pie chart
      pieChart.options.plugins.legend.labels.font = fontConfig;
      pieChart.options.plugins.tooltip.bodyFont = fontConfig;
      pieChart.options.plugins.tooltip.titleFont = fontConfig;
      
      // Update bar chart
      barChart.options.plugins.legend.labels.font = fontConfig;
      barChart.options.plugins.tooltip.bodyFont = fontConfig;
      barChart.options.plugins.tooltip.titleFont = fontConfig;
      barChart.options.scales.x.ticks.font = fontConfig;
      barChart.options.scales.x.title.font = fontConfig;
      barChart.options.scales.y.ticks.font = fontConfig;
      barChart.options.scales.y.title.font = fontConfig;
      
      pieChart.update();
      barChart.update();
    }, 100);
  });
});