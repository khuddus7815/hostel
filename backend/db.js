const mysql = require('mysql2/promise');

const dbConfig = {
  host: 'sdb-87.hosting.stackcp.net',
  user: 'tranetra',
  password: 'y*rIWWOqA9!T',
  database: 'tranetra-35313133cb7c',
  port: 41353,
  charset: 'utf8mb4'
};

const pool = mysql.createPool(dbConfig);

module.exports = { pool };
