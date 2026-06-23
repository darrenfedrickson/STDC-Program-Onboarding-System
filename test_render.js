const { JSDOM } = require("jsdom");
const dom = new JSDOM(`<!DOCTYPE html><div id="container"></div>`);
global.document = dom.window.document;

const dataArray = [{"Age":45,"Excel_Skill_Level":7},{"Age":26,"Excel_Skill_Level":6},{"Age":31,"Excel_Skill_Level":6},{"Age":30,"Excel_Skill_Level":10},{"Age":36,"Excel_Skill_Level":5},{"Age":20,"Excel_Skill_Level":8},{"Age":20,"Excel_Skill_Level":3},{"Age":29,"Excel_Skill_Level":1},{"Age":28,"Excel_Skill_Level":2},{"Age":26,"Excel_Skill_Level":4},{"Age":18,"Excel_Skill_Level":4},{"Age":23,"Excel_Skill_Level":6},{"Age":32,"Excel_Skill_Level":8},{"Age":33,"Excel_Skill_Level":3},{"Age":37,"Excel_Skill_Level":9},{"Age":33,"Excel_Skill_Level":3},{"Age":26,"Excel_Skill_Level":6},{"Age":28,"Excel_Skill_Level":3},{"Age":25,"Excel_Skill_Level":9},{"Age":41,"Excel_Skill_Level":5},{"Age":38,"Excel_Skill_Level":6},{"Age":33,"Excel_Skill_Level":5},{"Age":25,"Excel_Skill_Level":6},{"Age":31,"Excel_Skill_Level":1},{"Age":38,"Excel_Skill_Level":5},{"Age":45,"Excel_Skill_Level":6},{"Age":19,"Excel_Skill_Level":3},{"Age":23,"Excel_Skill_Level":8},{"Age":21,"Excel_Skill_Level":7},{"Age":30,"Excel_Skill_Level":7},{"Age":28,"Excel_Skill_Level":1},{"Age":20,"Excel_Skill_Level":5},{"Age":25,"Excel_Skill_Level":7},{"Age":22,"Excel_Skill_Level":6},{"Age":35,"Excel_Skill_Level":5},{"Age":27,"Excel_Skill_Level":5},{"Age":37,"Excel_Skill_Level":4},{"Age":40,"Excel_Skill_Level":7},{"Age":45,"Excel_Skill_Level":1},{"Age":35,"Excel_Skill_Level":8},{"Age":20,"Excel_Skill_Level":4}];

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

        if (!data || data.length === 0) return;
        if (data.length === 1 && Object.keys(data[0]).length === 1) return;

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

        if (canChart && chartDataObj) {
            const chartId = 'wchart_123';
            container.innerHTML += `<div>CANVAS ADDED</div>`;
            console.log("CHART ADDED");
        } else {
            container.innerHTML += `<div>TABLE ADDED</div>`;
            console.log("TABLE ADDED");
        }
    } catch (e) { console.error("ERROR CAUGHT:", e); }
}

const container = document.getElementById("container");
renderWidgetData(container, JSON.stringify(dataArray), undefined, undefined, false);
console.log(container.innerHTML);
