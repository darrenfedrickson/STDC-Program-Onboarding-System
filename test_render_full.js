const { JSDOM } = require("jsdom");
const dom = new JSDOM(`<!DOCTYPE html><html><body><div id="loadingRow"><div class="ai-w-content">Great! I've received the data...</div></div></body></html>`);
global.document = dom.window.document;

const rawPayloadFromDB = {"data":[{"Age":45,"ExcelSkill":7},{"Age":26,"ExcelSkill":6},{"Age":31,"ExcelSkill":6}],"chartTitle":"Age vs. Excel Skill for Program Registrants","isStacked":false};

// simulate result from fetch
const result = {
    data: rawPayloadFromDB.data,
    chart_type: "scatter"
    // Note: chartType is undefined, just like in submitAiPrompt
};

const chartColors = ['red', 'blue'];

function renderWidgetData(container, rawData, chartType, pChartTitle, pIsStacked) {
    if (!rawData) return;
    try {
        let data = JSON.parse(rawData);
        let chartTitle = pChartTitle || null;
        let isStacked = pIsStacked || false;

        if (data && !Array.isArray(data) && data.data) {
            chartTitle = data.chartTitle || chartTitle;
            isStacked = data.isStacked || isStacked;
            data = data.data;
        }

        if (!data || data.length === 0) {
            console.log("RETURN EARLY: length 0");
            return;
        }
        if (data.length === 1 && Object.keys(data[0]).length === 1) {
            console.log("RETURN EARLY: length 1, keys 1");
            return;
        }

        const keys = Object.keys(data[0]);
        let canChart = false;

        let type = data.length > 15 ? 'line' : (data.length <= 5 ? 'doughnut' : 'bar');
        if (chartType && chartType !== 'auto') {
            type = chartType.toLowerCase();
            if (!['bar', 'line', 'pie', 'doughnut', 'scatter'].includes(type)) type = 'bar';
        }

        let chartDataObj = null;
        let multiCharts = null;

        if (keys.length === 2 || keys.length === 3) {
            let labelKey = null, valueKey = null;
            for (let k of keys) {
                if (!isNaN(parseFloat(data[0][k])) && isFinite(data[0][k])) valueKey = k;
                else if (k !== valueKey) labelKey = k;
            }
            if (!labelKey) labelKey = keys[0];
            if (valueKey && labelKey && valueKey !== labelKey) canChart = true;

            if (canChart && data.length > 1) {
                const labels = data.map(r => r[labelKey]);
                const values = data.map(r => parseFloat(r[valueKey]));

                chartDataObj = {
                    labels: labels,
                    datasets: [{
                        label: valueKey, data: values,
                        backgroundColor: chartColors,
                        borderColor: chartColors.map(c => c.replace('0.8', '1')),
                        borderWidth: 1, borderRadius: type === 'bar' ? 6 : 0
                    }]
                };
            } else {
                canChart = false;
            }
        }

        if (canChart && multiCharts) {
            console.log("MULTI CHART");
        } else if (canChart && chartDataObj) {
            const chartId = 'wchart_' + Date.now();
            container.innerHTML += `
                <div class="ai-w-chart">
                    <canvas id="${chartId}"></canvas>
                </div>
                <div class="export-buttons">...</div>
            `;
            console.log("CHART APPENDED!");
        } else {
            console.log("TABLE APPENDED!");
        }
    } catch (e) { console.error("ERROR CAUGHT:", e); }
}

const contentDiv = document.querySelector('.ai-w-content');
renderWidgetData(contentDiv, JSON.stringify(result.data), result.chartType, result.chartTitle, result.isStacked);

console.log("FINAL HTML:", contentDiv.innerHTML);

