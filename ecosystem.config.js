module.exports = {
  apps: [
    {
      name: "laravel-echo-server",
      script: "laravel-echo-server",
      args: "start",
      exec_mode: "fork",
      watch: false
    },
    {
      name: "queue-worker",
      script: "php",
      args: "artisan queue:work --sleep=3 --tries=3",
      exec_mode: "fork",
      watch: false
    }
  ]
}
