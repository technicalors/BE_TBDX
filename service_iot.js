/**************************************************
 *  service_iot.js
 *  Chức năng:
 *  - Login auth (retry khi mất auth)
 *  - Fetch dữ liệu từ danh sách devices (1s/lần)
 *  - Bỏ qua device lỗi
 *  - Chỉ gửi nếu dữ liệu mới, không trùng với lần trước
 **************************************************/

const axios = require('axios');

// ====== Config ======
const TELEMETRY_URL = "http://113.161.189.44:3030/api/plugins/telemetry/DEVICE";
const AUTH_URL      = "http://113.161.189.44:3030/api/auth/login";
const POST_URL      = "http://127.0.0.1:8000/api/websocket";

const USER_CREDENTIALS = {
    username: 'messystem@gmail.com',
    password: 'mesors@2023'
};

const DEVICES = [
    '2262b3d0-85db-11ee-8392-a51389126dc6', // Da06
    '34055200-85db-11ee-8392-a51389126dc6', // Da05
    '0a6afda0-85db-11ee-8392-a51389126dc6', // Pr06
    'ffd778a0-85da-11ee-8392-a51389126dc6', // Pr15
    'e9aba8d0-85da-11ee-8392-a51389126dc6', // So01
    'd9397550-ad38-11ef-a8bd-45ae64f28680', // Pr11
    'ed675240-ad38-11ef-a8bd-45ae64f28680', // Pr12
    'f5957000-ad38-11ef-a8bd-45ae64f28680', // Pr16
    '69f8f0e0-ad3c-11ef-a8bd-45ae64f28680', // CH02
    '72f81a40-ad3c-11ef-a8bd-45ae64f28680'  // CH03
];

const MachineID = {
    '2262b3d0-85db-11ee-8392-a51389126dc6': 'Da06',
    '34055200-85db-11ee-8392-a51389126dc6': 'Da05',
    '0a6afda0-85db-11ee-8392-a51389126dc6': 'Pr06',
    'ffd778a0-85da-11ee-8392-a51389126dc6': 'Pr15',
    'e9aba8d0-85da-11ee-8392-a51389126dc6': 'So01',
    'd9397550-ad38-11ef-a8bd-45ae64f28680': 'Pr11',
    'ed675240-ad38-11ef-a8bd-45ae64f28680': 'Pr12',
    'f5957000-ad38-11ef-a8bd-45ae64f28680': 'Pr16',
    '69f8f0e0-ad3c-11ef-a8bd-45ae64f28680': 'CH02',
    '72f81a40-ad3c-11ef-a8bd-45ae64f28680': 'CH03',
}

// ====== State ======
let token;
const previousData = {}; // lưu dữ liệu lần trước

// ====== Utility ======
function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// ====== 1) Authenticate ======
async function authenticate() {
    while (true) {
        try {
            console.log("🔑 Authenticating...");
            const response = await axios.post(AUTH_URL, USER_CREDENTIALS);
            token = response.data.token;
            console.log("✅ Auth success");
            return token;
        } catch (error) {
            console.error("❌ Auth failed:", error.message);
            await delay(2000); // retry sau 2s
        }
    }
}

// ====== 2) Fetch telemetry for 1 device ======
async function fetchTelemetryData(device) {
    try {
        const response = await axios.get(
            `${TELEMETRY_URL}/${device}/values/timeseries`,
            { headers: { 'Authorization': `Bearer ${token}` }, timeout: 5000 }
        );

        return {
            device_id: device,
            Pre_Counter:    response.data?.Pre_Counter?.[0]?.value ?? 0,
            Set_Counter:    response.data?.Set_Counter?.[0]?.value ?? 0,
            Error_Counter:  response.data?.Error_Counter?.[0]?.value ?? 0,
            Machine_Status: response.data?.Machine_Status?.[0]?.value ?? 0,
            Length_Cut: response.data?.Length_Cut?.[0]?.value ?? 0,
        };
    } catch (err) {
        // Nếu 401 => auth lại
        if (err.response && err.response.status === 401) {
            console.warn(`⚠️ Unauthorized for ${device}, re-auth...`);
            await authenticate();
        } else {
            console.error(`❌ Fetch error for ${MachineID[device]}:`, err.message);
        }
        return null; // báo lỗi, bỏ qua device
    }
}

// ====== 3) So sánh dữ liệu cũ/mới ======
function isDuplicate(prev, current) {
    if (!prev) return false;
    return prev.Pre_Counter   === current.Pre_Counter &&
           prev.Set_Counter   === current.Set_Counter &&
           prev.Error_Counter === current.Error_Counter &&
           prev.Machine_Status=== current.Machine_Status;
}

// ====== 4) Gửi dữ liệu lên server ======
async function postData(data) {
    try {
        var { data: res } = await axios.post(POST_URL, data, { timeout: 5000 });
        console.log(`📤 Posted data for device ${MachineID[data.device_id]}`, res.data);
    } catch (err) {
        console.error(`❌ Post error for ${MachineID[data.device_id]}:`, err.message);
    }
}

// ====== 5) Xử lý từng device ======
async function processDevice(device) {
    const data = await fetchTelemetryData(device);
    if (!data) return; // bỏ qua nếu fetch lỗi

    const prev = previousData[device];
    if (!isDuplicate(prev, data)) {
        previousData[device] = data;
        await postData(data);
    } else {
        // console.log(`⏩ Duplicate data for ${device}, skip`);
    }
}

// ====== 6) Main loop ======
async function main() {
    await authenticate();

    setInterval(async () => {
        for (const device of DEVICES) {
            processDevice(device);
        }
    }, 1000); // lặp mỗi 1 giây
}

// ====== Start ======
main();
