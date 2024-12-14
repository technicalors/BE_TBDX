const axios = require('axios');

// Configuration Constants
const TELEMETRY_URL = "http://113.176.95.167:3030/api/plugins/telemetry/DEVICE";
const AUTH_URL = "http://113.176.95.167:3030/api/auth/login";
const POST_URL = "https://backtbdx.ouransoft.vn/api/websocket";
const POST_MACHINE_STATUS_URL = "https://backtbdx.ouransoft.vn/api/websocket-machine-status";
const POST_MACHINE_PARAMS_URL = "https://backtbdx.ouransoft.vn/api/websocket-machine-params";
const USER_CREDENTIALS = {
    username: 'messystem@gmail.com',
    password: 'mesors@2023'
};
const DEVICES =  ['2262b3d0-85db-11ee-8392-a51389126dc6', '34055200-85db-11ee-8392-a51389126dc6', '0a6afda0-85db-11ee-8392-a51389126dc6', 'ffd778a0-85da-11ee-8392-a51389126dc6', 'e9aba8d0-85da-11ee-8392-a51389126dc6',
    'd9397550-ad38-11ef-a8bd-45ae64f28680','ed675240-ad38-11ef-a8bd-45ae64f28680','f5957000-ad38-11ef-a8bd-45ae64f28680','69f8f0e0-ad3c-11ef-a8bd-45ae64f28680','72f81a40-ad3c-11ef-a8bd-45ae64f28680'
];
const RETRY_INTERVALS = {
    fetchError: 2000, // in ms
    duplicateData: 1000 // in ms
};

// Store previous data per device to handle multiple devices
const previousData = {};
const previousStatus = {};

// Function to authenticate and retrieve token
async function authenticate() {
    try {
        const response = await axios.post(AUTH_URL, USER_CREDENTIALS);
        return response.data.token;
    } catch (error) {
        console.error('Authentication failed:', error.message);
        // Retry authentication after a delay
        await delay(RETRY_INTERVALS.fetchError);
        return authenticate();
    }
}

// Utility function for delays
function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Function to fetch telemetry data for a device
async function fetchTelemetryData(device, token) {
    try {
        const response = await axios.get(`${TELEMETRY_URL}/${device}/values/timeseries`, {
            headers: { 'Authorization': `Bearer ${token}` },
            timeout: 5000 // Set a timeout for the request
        });
        
        if (response.status === 401) {
            console.warn('Unauthorized access. Re-authenticating...');
            const newToken = await authenticate();
            return fetchTelemetryData(device, newToken);
        }
        
        const { Pre_Counter, Set_Counter, Error_Counter, Machine_Status } = response.data;
        
        const data = {
            device_id: device,
            Pre_Counter: Pre_Counter ? (Pre_Counter[0]?.value ?? 0) : 0,
            Set_Counter: Set_Counter ? (Set_Counter[0]?.value ?? 0) : 0,
            Error_Counter: Error_Counter ? (Error_Counter[0]?.value ?? 0) : 0,
            Machine_Status: Machine_Status ? (Machine_Status[0]?.value ?? 0) : 0,
        };

        const status = {
            device_id: device,
            Machine_Status: Machine_Status[0]?.value ?? "",
        };

        const params = {};
        Object.keys(response.data ?? {}).forEach(key=>{
            params[key] = response.data[key][0]?.value ?? "";
        });
        params['device_id'] = device;
        return {data, status, params};

    } catch (error) {
        if (error.response && error.response.status === 401) {
            console.warn('Received 401 status. Re-authenticating...');
            const newToken = await authenticate();
            return fetchTelemetryData(device, newToken);
        } else {
            console.error(`Error fetching data for device ${device}:`, error);
            throw error;
        }
    }
}

// Function to send data to the POST endpoint
async function postData(data) {
    try {
        const response = await axios.post(POST_URL, data, { timeout: 5000 });
        console.log('Data posted successfully:', { ...data, ...response.data });
    } catch (error) {
        console.error('Error posting data:', error);
    }
}

// Function to send data to the POST endpoint
async function postMachineStatus(data) {
    try {
        const response = await axios.post(POST_MACHINE_STATUS_URL, data, { timeout: 5000 });
        console.log('Status posted successfully:', { ...data, ...response.data });
    } catch (error) {
        console.error('Error posting status:', error.message);
    }
}

// Function to send data to the POST endpoint
async function postMachineParams(data) {
    try {
        const response = await axios.post(POST_MACHINE_PARAMS_URL, data, { timeout: 5000 });
        console.log('Params posted successfully:', { ...response.data });
    } catch (error) {
        console.error('Error posting params:', error.message);
    }
}

// Function to process data: check for duplicates and send if necessary
async function processData(device, token) {
    try {
        const {data, status, params} = await fetchTelemetryData(device, token);
        const prevStatus = previousStatus[device];
        if (!isStatusDuplicate(prevStatus, status)) {
            previousStatus[device] = status;
            await postMachineStatus(status);
        } else {
            console.log(`Duplicate data for device ${device}, not sending.`);
        }

        await postMachineParams(params);

        if (!data) return;
        const prev = previousData[device];
        if (!isDataDuplicate(prev, data)) {
            previousData[device] = data;
            await postData(data);
        } else {
            console.log(`Duplicate data for device ${device}, not sending.`);
        }

        
    } catch (error) {
        console.log(`Retrying data fetch for device ${device} after error.`);
    }
}

// Utility function to compare current data with previous data
function isDataDuplicate(prev, current) {
    if (!prev) return false;
    return prev.Pre_Counter === current.Pre_Counter &&
           prev.Set_Counter === current.Set_Counter &&
           prev.Error_Counter === current.Error_Counter &&
           prev.Machine_Status === current.Machine_Status;
}

function isStatusDuplicate(prev, current) {
    if (!prev) return false;
    return prev.Machine_Status === current.Machine_Status;
}

// Main function to initialize data fetching for all devices
async function initialize() {
    const token = await authenticate();

    DEVICES.forEach(device => {
        const fetchLoop = async () => {
            try {
                await processData(device, token);
                setTimeout(fetchLoop, RETRY_INTERVALS.duplicateData);
            } catch (error) {
                console.error(`Error in fetch loop for device ${device}:`, error.message);
                setTimeout(fetchLoop, RETRY_INTERVALS.fetchError);
            }
        };
        fetchLoop();
    });
}

// Start the process
initialize();