const express = require("express");
const cors = require("cors");
const app = express();
const db = require("../db");
const { jwtGenerator, jwtDecoder } = require("../utils/jwtToken");

app.use(cors());
app.use(express.json());

const decodeUser = async (token) => {
  try {
    const decodedToken = jwtDecoder(token);
    console.log(decodedToken);

    const { user_id, type } = decodedToken.user;
    let userInfo;

    if (type === "student") {
      const query = `
        SELECT student_id, room, block_id
        FROM student 
        WHERE student_id = ?
      `;

      const [result] = await db.pool.query(query, [user_id]);
      console.log(result);
      if (result.length > 0) {
        userInfo = result[0];
      }
    }

    if (type === "warden") {
      const query = `
        SELECT warden_id,  block_id
        FROM warden 
        WHERE warden_id = ?
      `;

      const [result] = await db.pool.query(query, [user_id]);

      if (result.length > 0) {
        userInfo = result[0];
      }
    }

    return userInfo;
  } catch (err) {
    console.error("here111", err.message);
  }
};

exports.postComplaints = async (req, res) => {
  try {
    const token = req.headers.authorization;
    console.log(token);
    const userInfo = await decodeUser(token);

    const { student_id, block_id } = userInfo;

    const { name, description, room, is_completed, assigned_at } = req.body;

    const query = `insert into complaint 
            (name, block_id, 
            student_id, 
            description, room, is_completed, created_at,
            assigned_at) 
            values (?,?,?,?,?,?,?,?)`;

    const [newComplaint] = await db.pool.query(query, [
      name,
      block_id,
      student_id,
      description,
      room,
      false,
      new Date().toISOString().slice(0, 19).replace('T', ' '),
      null,
    ]);
    res.json(newComplaint);
  } catch (err) {
    console.log(err.message);
  }
};

exports.putComplaintsByid = async (req, res) => {
  const token = req.headers.authorization;
  const decodedToken = jwtDecoder(token);
  console.log(decodedToken);
  const { user_id, type } = decodedToken.user;

  try {
    const { id } = req.params;

    if (type === "warden") {
      const [result] = await db.pool.query(
        "UPDATE complaint SET is_completed = NOT is_completed, assigned_at = CURRENT_TIMESTAMP WHERE id = ?",
        [id]
      );
      res.json(result);
    } else {
      res.status(404).json({ error: "Complaint not found" });
    }
  } catch (err) {
    console.log(err.message);
    res.status(500).json({ error: "Internal Server Error" });
  }
};

exports.getAllComplaintsByUser = async (req, res) => {
  const token = req.headers.authorization;
  console.log(token);
  const decodedToken = jwtDecoder(token);
  console.log(decodedToken);

  const { user_id, type } = decodedToken.user;

  try {
    if (type === "warden") {
      const [allComplaints] = await db.pool.query(
        "SELECT * FROM complaint ORDER BY created_at DESC"
      );
      res.json(allComplaints);
    } else if (type === "student") {
      const [myComplaints] = await db.pool.query(
        "SELECT * FROM complaint WHERE student_id = ? ORDER BY created_at DESC",
        [user_id]
      );
      res.json(myComplaints);
    } else {
      res.status(403).json({ error: "Unauthorized" });
    }
  } catch (err) {
    console.error(err.message);
    res.status(500).json({ error: "Internal Server Error" });
  }
};

exports.getUserType = async (req, res) => {
  try {
    const token = req.headers.authorization;
    console.log(token);
    const decodedToken = jwtDecoder(token);
    console.log(decodedToken);
    const { type } = decodedToken.user;

    res.json({ userType: type });
  } catch (err) {
    console.error(err.message);
    res.status(500).json({ error: "Internal Server Error" });
  }
};

exports.getUserDetails = async (req, res) => {
  try {
    const token = req.headers.authorization;
    console.log(token);
    const decodedToken = jwtDecoder(token);
    console.log(decodedToken);
    const { user_id, type } = decodedToken.user;

    console.log("Decoded Token:", decodedToken);

    console.log("User Type:", type);

    console.log("User ID:", user_id);

    if (type == "student") {
      const [studentDetails] = await db.pool.query(
        `SELECT u.full_name, u.email, u.phone, s.usn, b.block_id, b.block_name, s.room
      FROM users u, student s, block b
      WHERE u.user_id = ? AND u.user_id = s.student_id AND s.block_id = b.block_id`,
        [user_id]
      );
      res.json(studentDetails);
    }
    if (type == "warden") {
      const [wardenDetails] = await db.pool.query(
        `select u.full_name,u.email,u.phone
                                                  from users u 
                                                  where user_id=?`,
        [user_id]
      );
      res.json(wardenDetails);
    }
  } catch (err) {
    console.error(err.message);
    res.status(500).json({ error: "Internal Server Error" });
  }
};

exports.deleteComplaints = async (req, res) => {
  try {
    const token = req.headers.authorization;
    console.log(token);
    const decodedToken = jwtDecoder(token);
    console.log(decodedToken);
    const { type } = decodedToken.user;
    const { id } = req.params;

    if (type == "warden") {
      const [deleteComplaint] = await db.pool.query(
        `delete from complaint where id = ?`,
        [id]
      );
      res.json("complaint deleted");
    }
  } catch (err) {
    console.log(err.message);
    res.status(500).json({ error: "Internal Server Error" });
  }
};