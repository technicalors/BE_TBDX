module.exports = {
  apps: [
    {
      name: "service-iot",
      script: "service_iot.js",
      interpreter: "node",
    },
    {
      name: "queue-worker",
      script: "php",
      args: "artisan queue:work --sleep=3 --tries=3",
      exec_mode: "fork",
      watch: false
    },
    {
      name: "queue-worker-Da06",
      script: "php",
      args: "artisan queue:work --queue=device_2262b3d0-85db-11ee-8392-a51389126dc6",
      // cwd: "C:/Projects/BE_TBDX",
      exec_mode: "fork",
      watch: false,
      interpreter: "none"
    },
    {
      name: "queue-worker-Da05",
      script: "php",
      args: "artisan queue:work --queue=device_34055200-85db-11ee-8392-a51389126dc6",
      // cwd: "C:/Projects/BE_TBDX",
      exec_mode: "fork",
      watch: false,
      interpreter: "none"
    },
    {
      name: "queue-worker-Pr06",
      script: "php",
      args: "artisan queue:work --queue=device_0a6afda0-85db-11ee-8392-a51389126dc6",
      // cwd: "C:/Projects/BE_TBDX",
      exec_mode: "fork",
      watch: false,
      interpreter: "none"
    },
    {
      name: "queue-worker-Pr15",
      script: "php",
      args: "artisan queue:work --queue=device_ffd778a0-85da-11ee-8392-a51389126dc6",
      // cwd: "C:/Projects/BE_TBDX",
      exec_mode: "fork",
      watch: false,
      interpreter: "none"
    },
    {
      name: "queue-worker-So01",
      script: "php",
      args: "artisan queue:work --queue=device_e9aba8d0-85da-11ee-8392-a51389126dc6",
      // cwd: "C:/Projects/BE_TBDX",
      exec_mode: "fork",
      watch: false,
      interpreter: "none"
    },
    {
      name: "queue-worker-Pr11",
      script: "php",
      args: "artisan queue:work --queue=device_d9397550-ad38-11ef-a8bd-45ae64f28680",
      // cwd: "C:/Projects/BE_TBDX",
      exec_mode: "fork",
      watch: false,
      interpreter: "none"
    },
    {
      name: "queue-worker-Pr12",
      script: "php",
      args: "artisan queue:work --queue=device_ed675240-ad38-11ef-a8bd-45ae64f28680",
      // cwd: "C:/Projects/BE_TBDX",
      exec_mode: "fork",
      watch: false,
      interpreter: "none"
    },
    {
      name: "queue-worker-Pr16",
      script: "php",
      args: "artisan queue:work --queue=device_f5957000-ad38-11ef-a8bd-45ae64f28680",
      // cwd: "C:/Projects/BE_TBDX",
      exec_mode: "fork",
      watch: false,
      interpreter: "none"
    },
    {
      name: "queue-worker-CH02",
      script: "php",
      args: "artisan queue:work --queue=device_69f8f0e0-ad3c-11ef-a8bd-45ae64f28680",
      // cwd: "C:/Projects/BE_TBDX",
      exec_mode: "fork",
      watch: false,
      interpreter: "none"
    },
    {
      name: "queue-worker-CH03",
      script: "php",
      args: "artisan queue:work --queue=device_72f81a40-ad3c-11ef-a8bd-45ae64f28680",
      // cwd: "C:/Projects/BE_TBDX",
      exec_mode: "fork",
      watch: false,
      interpreter: "none"
    }
  ]
}
