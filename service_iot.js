/**************************************************
 *  Gắn thêm:  npm i axios p-limit
 **************************************************/
const axios = require('axios');
const pLimit = require('p-limit');

// ====== Configuration Constants ======
const TELEMETRY_URL = "http://113.161.189.44:3030/api/plugins/telemetry/DEVICE";
const AUTH_URL      = "http://113.161.189.44:3030/api/auth/login";
// const LOCAL_URL      = "http://127.0.0.1:8001";
const BASE_URL      = "http://backtbdx.ouransoft.vn"
const POST_URL                  = `${BASE_URL}/api/websocket`;
const POST_MACHINE_STATUS_URL   = `${BASE_URL}/api/websocket-machine-status`;
const POST_MACHINE_PARAMS_URL   = `${BASE_URL}/api/websocket-machine-params`;

const USER_CREDENTIALS = {
    username: 'messystem@gmail.com',
    password: 'mesors@2023'
};

const DEVICES =  [
    '2262b3d0-85db-11ee-8392-a51389126dc6', //Da06
    '34055200-85db-11ee-8392-a51389126dc6', //Da05
    '0a6afda0-85db-11ee-8392-a51389126dc6', //Pr06
    'ffd778a0-85da-11ee-8392-a51389126dc6', //Pr15
    'e9aba8d0-85da-11ee-8392-a51389126dc6', //So01
    'd9397550-ad38-11ef-a8bd-45ae64f28680', //Pr11
    'ed675240-ad38-11ef-a8bd-45ae64f28680', //Pr12
    'f5957000-ad38-11ef-a8bd-45ae64f28680', //Pr16
    '69f8f0e0-ad3c-11ef-a8bd-45ae64f28680', //CH02
    '72f81a40-ad3c-11ef-a8bd-45ae64f28680'  //CH03
];

// Các khoảng thời gian (ms)
const RETRY_INTERVALS = {
    fetchError:    2000,  // Lỗi fetch => chờ 2s rồi thử lại
    duplicateData: 1000   // Tạm dùng làm interval loop
};

// ====== Store states for duplicate checking ======
const previousData   = {};
const previousStatus = {};
let token;

// ====== 1) Function: authenticate -> lấy token ======
async function authenticate() {
    try {
        const response = await axios.post(AUTH_URL, USER_CREDENTIALS);
        token = response.data.token;
        return response.data.token;
    } catch (error) {
        console.error('Authentication failed:', error.message);
        // Retry sau 2s
        await delay(RETRY_INTERVALS.fetchError);
        return authenticate();
    }
}

// ====== 2) Utility: delay ======
function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// ====== 3) Fetch telemetry data cho 1 device ======
async function fetchTelemetryData(device, token) {
    try {
        const response = await axios.get(
            `${TELEMETRY_URL}/${device}/values/timeseries`,
            {
                headers: { 'Authorization': `Bearer ${token}` },
                timeout: 2000
            }
        );

        // Nếu token cũ, bị 401 -> login lại
        if (response.status === 401) {
            console.warn('Unauthorized. Re-authenticating...');
            const newToken = await authenticate();
            return fetchTelemetryData(device, newToken);
        }

        // Data parse
        const { Pre_Counter, Set_Counter, Error_Counter, Machine_Status } = response.data;
        if(isNaN(Pre_Counter)){
            throw new Error("Data không hợp lệ");
        }
        const data = {
            device_id:       device,
            Pre_Counter:     Pre_Counter    ? (Pre_Counter[0]?.value    ?? 0) : 0,
            Set_Counter:     Set_Counter    ? (Set_Counter[0]?.value    ?? 0) : 0,
            Error_Counter:   Error_Counter  ? (Error_Counter[0]?.value  ?? 0) : 0,
            Machine_Status:  Machine_Status ? (Machine_Status[0]?.value ?? 0) : 0,
        };

        const status = {
            device_id:       device,
            Machine_Status:  Machine_Status ? (Machine_Status[0]?.value ?? "") : ""
        };

        // Demo params: nếu cần post tất cả key-value
        const params = {};
        Object.keys(response.data ?? {}).forEach(key => {
            params[key] = response.data[key][0]?.value ?? "";
        });
        params['device_id'] = device;

        return { data, status, params };

    } catch (error) {
        // Nếu 401 do token hết hạn => login lại
        if (error.response && error.response.status === 401) {
            console.warn('Received 401 status. Re-authenticating...');
            const newToken = await authenticate();
            return fetchTelemetryData(device, newToken);
        } else {
            console.error(`Error fetching data for device ${device}:`, error.message);
            throw error;
        }
    }
}

// ====== 4) postData, postMachineStatus, postMachineParams ======
async function postData(data) {
    try {
        // In log sample
        if (data.device_id === 'f5957000-ad38-11ef-a8bd-45ae64f28680') {
            console.log('Data posted:', data);
        }
        return await axios.post(POST_URL, data, { timeout: 2000 });
    } catch (error) {
        console.error('Error posting data:', error?.response?.message);
    }
}

async function postMachineStatus(data) {
    try {
        await axios.post(POST_MACHINE_STATUS_URL, data, { timeout: 2000 });
        // console.log('Status posted:', data);
    } catch (error) {
        console.error('Error posting status:', error.message);
    }
}

async function postMachineParams(data) {
    try {
        const response = await axios.post(POST_MACHINE_PARAMS_URL, data, { timeout: 2000 });
        console.log('Params posted:', response.data);
    } catch (error) {
        console.error('Error posting params:', error.message);
    }
}

// ====== 5) Hàm xử lý data cho 1 device (fetch + check duplicate + post) ======
let lastParamsSentTime = 0;
async function processData(device, token) {
    try {
        const { data, status, params } = await fetchTelemetryData(device, token);

        // Kiểm tra duplicate status
        const prevStatus = previousStatus[device];
        if (!isStatusDuplicate(prevStatus, status)) {
            previousStatus[device] = status;
            await postMachineStatus(status);
        } else {
            console.log(`Duplicate status for device ${device}, not sending.`);
        }

        // (Nếu cần gửi tất cả params)
        const now = Date.now();
        if (now - lastParamsSentTime >= 60000) {
            await postMachineParams(params);
            lastParamsSentTime = now;
        }

        // Kiểm tra duplicate data
        if (data) {
            const prev = previousData[device];
            if (!isDataDuplicate(prev, data)) {
                previousData[device] = data;
                const startTime = performance.now();
                var res = await postData(data);
                const endTime = performance.now();
                const timeTaken = (endTime - startTime) / 1000; // sec
                console.log(`Thời gian xử lý (từ FE): ${timeTaken} s`);
                console.log(data);
            } else {
                console.log(`Duplicate data for device ${device}, not sending.`);
            }
        }
    } catch (error) {
        // Nếu lỗi, ta log, nhưng không vỡ vòng lặp
        console.log(`Error -> device ${device}:`, error.message);
        // Có thể chờ 2s trước khi cho device này fetch lại
        await delay(RETRY_INTERVALS.fetchError);
    }
}

// ====== 6) Check duplicate functions ======
function isDataDuplicate(prev, current) {
    if (!prev) return false;
    return prev.Pre_Counter   === current.Pre_Counter &&
           prev.Set_Counter   === current.Set_Counter &&
           prev.Error_Counter === current.Error_Counter &&
           prev.Machine_Status=== current.Machine_Status;
}

function isStatusDuplicate(prev, current) {
    if (!prev) return false;
    return prev.Machine_Status === current.Machine_Status;
}

// ====== 7) Giới hạn số request đồng thời (p-limit) ======
const limit = pLimit(3); 
// -> Chỉ cho phép 3 request chạy cùng lúc. 
//   Tuỳ ý sửa con số này theo tài nguyên mạng/server.

// ====== 8) Hàm xử lý tất cả devices theo vòng lặp ======
async function runDevices(token) {
    // Xử lý tất cả device song song nhưng giới hạn concurrency
    await Promise.all(
        DEVICES.map(device => limit(() => processData(device, token)))
    );
}

// ====== 9) Hàm main: vừa login, vừa lặp fetch + post ======
async function initialize() {
    while (true) {
        try {
            // Mỗi vòng lặp ta auth 1 lần (hoặc có thể cache token)
            // const token = await authenticate();

            // Gọi xử lý tất cả devices (đã limit concurrency)
            await runDevices(token);

            // Chờ 1 khoảng => lặp
            await delay(RETRY_INTERVALS.duplicateData);

        } catch (err) {
            // Nếu có lỗi ngoài ý muốn => log & chờ
            console.error("Error in main loop:", err.message);
            await delay(RETRY_INTERVALS.fetchError);
        }
    }
}

// ====== 10) Bắt đầu ======
initialize();
