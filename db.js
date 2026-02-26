const { Pool } = require('pg');

const pool = new Pool({
  user: 'user',           // Según tu docker-compose
  host: 'postgres',       // Según tu docker-compose
  database: 'db-api-daw', 
  password: 'pass1234',   
  port: 5432,
});

module.exports = pool; // Exportamos directamente el pool