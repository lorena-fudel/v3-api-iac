const jwt = require('jsonwebtoken');

exports.login = (req, res) => {
    const { username, password } = req.body;

    if (username === 'lorena' && password === '1234') {
        const token = jwt.sign({ user: username }, process.env.JWT_SECRET, { expiresIn: '1h' });
        console.log("✅ Token generado para:", username);
        return res.json({ token });
    }
    return res.status(401).json({ error: "Credenciales incorrectas" });
};