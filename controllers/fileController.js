const fs = require('fs');
const path = require('path');

exports.getHistory = (req, res) => {
    const rutaArchivo = path.resolve(__dirname, '../introducir-texto.txt');
    
    fs.readFile(rutaArchivo, 'utf8', (err, contenido) => {
        if (err) {
            return res.status(200).send("El historial está vacío o el archivo no existe.");
        }
        res.header("Content-Type", "text/plain");
        return res.send(contenido);
    });
};

exports.getSaludar = (req, res) => {
    const nombreUsuario = req.user ? req.user.user : "Usuario";
    const horaActual = new Date().toLocaleTimeString('es-ES', { 
        hour: '2-digit', minute: '2-digit', second: '2-digit' 
    });

    return res.json({ 
        mensaje: `¡Hola ${nombreUsuario}, bienvenido a la API!`,
        hora: horaActual,
        foto: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=500&q=80" 
    });
};