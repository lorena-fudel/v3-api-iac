const express = require('express');
const router = express.Router();
const verificarToken = require('../middlewares/authMiddleware');
const fileController = require('../controllers/fileController');

// Las rutas deben usar las funciones del controlador
router.get('/ver-historial', verificarToken, fileController.getHistory);
router.get('/saludar', verificarToken, fileController.getSaludar);

module.exports = router;