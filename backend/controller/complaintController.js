const { pool } = require('../db');

const postComplaints = async (req, res) => {
  try {
    const { title, description, user_id } = req.body;
    
    const [result] = await pool.execute(
      "INSERT INTO complaints (title, description, user_id, status) VALUES (?, ?, ?, 'pending')",
      [title, description, user_id]
    );

    res.json({ message: "Complaint posted successfully", complaint_id: result.insertId });
  } catch (err) {
    console.error(err.message);
    res.status(500).json("Server error");
  }
};

const getAllComplaintsByUser = async (req, res) => {
  try {
    const [complaints] = await pool.execute(
      "SELECT * FROM complaints ORDER BY created_at DESC"
    );
    res.json(complaints);
  } catch (err) {
    console.error(err.message);
    res.status(500).json("Server error");
  }
};

const putComplaintsByid = async (req, res) => {
  try {
    const { id } = req.params;
    const { status } = req.body;
    
    await pool.execute(
      "UPDATE complaints SET status = ? WHERE complaint_id = ?",
      [status, id]
    );

    res.json({ message: "Complaint updated successfully" });
  } catch (err) {
    console.error(err.message);
    res.status(500).json("Server error");
  }
};

const deleteComplaints = async (req, res) => {
  try {
    const { id } = req.params;
    
    await pool.execute(
      "DELETE FROM complaints WHERE complaint_id = ?",
      [id]
    );

    res.json({ message: "Complaint deleted successfully" });
  } catch (err) {
    console.error(err.message);
    res.status(500).json("Server error");
  }
};

const getUserType = async (req, res) => {
  try {
    const { user_id } = req.query;
    
    const [users] = await pool.execute(
      "SELECT type FROM users WHERE user_id = ?",
      [user_id]
    );

    if (users.length === 0) {
      return res.status(404).json("User not found");
    }

    res.json({ type: users[0].type });
  } catch (err) {
    console.error(err.message);
    res.status(500).json("Server error");
  }
};

const getUserDetails = async (req, res) => {
  try {
    const { id } = req.params;
    
    const [users] = await pool.execute(
      "SELECT user_id, full_name, email, phone, type FROM users WHERE user_id = ?",
      [id]
    );

    if (users.length === 0) {
      return res.status(404).json("User not found");
    }

    res.json(users[0]);
  } catch (err) {
    console.error(err.message);
    res.status(500).json("Server error");
  }
};

module.exports = {
  postComplaints,
  putComplaintsByid,
  getAllComplaintsByUser,
  getUserType,
  getUserDetails,
  deleteComplaints
};
