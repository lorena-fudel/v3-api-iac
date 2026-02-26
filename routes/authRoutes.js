const express = require('express');
const router = express.Router();
const authController = require('../controllers/authController');

// SOLO '/login', el '/auth' ya lo pone app.js
router.post('/login', authController.login); 

module.exports = router;