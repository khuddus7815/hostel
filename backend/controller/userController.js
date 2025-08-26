const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
const { pool } = require('../db');

const jwtGenerator = (userId, type) => {
  const payload = { user_id: userId, type: type };
  return jwt.sign(payload, process.env.JWT_SECRET || 'defaultsecret', { expiresIn: '1hr' });
};

const userRegister = async (req, res) => {
  try {
    const { full_name, email, phone, password, type, block_id, usn, room } = req.body;

    // Check if user already exists
    const [existingUser] = await pool.execute(
      "SELECT * FROM users WHERE email = ?",
      [email]
    );

    if (existingUser.length > 0) {
      return res.status(401).json("User already exist!");
    }

    const bcryptPassword = await bcrypt.hash(password, 10);
    const [result] = await pool.execute(
      "INSERT INTO users (full_name, email, phone, password, type) VALUES (?, ?, ?, ?, ?)",
      [full_name, email, phone, bcryptPassword, type]
    );

    const userId = result.insertId;
    const jwtToken = jwtGenerator(userId, type);

    if (type === "student") {
      await pool.execute(
        "INSERT INTO student (student_id, block_id, usn, room) VALUES (?, ?, ?, ?)",
        [userId, block_id, usn, room]
      );
    } else if (type === "warden") {
      await pool.execute(
        "INSERT INTO warden (warden_id, block_id) VALUES (?, ?)",
        [userId, block_id]
      );
    }

    res.json({ jwtToken });
  } catch (err) {
    console.error(err.message);
    res.status(500).json("Server error");
  }
};

const userLogin = async (req, res) => {
  try {
    const { email, password } = req.body;

    const [users] = await pool.execute(
      "SELECT user_id, password, type FROM users WHERE email = ?",
      [email]
    );

    if (users.length === 0) {
      return res.status(401).json("Invalid Credential");
    }

    const user = users[0];
    const validPassword = await bcrypt.compare(password, user.password);

    if (!validPassword) {
      return res.status(401).json("Invalid Credential");
    }

    const jwtToken = jwtGenerator(user.user_id, user.type);
    res.json({ jwtToken });
  } catch (err) {
    console.error(err.message);
    res.status(500).json("Server error");
  }
};

module.exports = { userRegister, userLogin };
