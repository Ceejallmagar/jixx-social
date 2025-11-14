<?php
require_once 'config.php';

class Database {
    public function getPostsByUser($userId, $limit = 20, $offset = 0) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, u.first_name, u.last_name, u.avatar, COUNT(DISTINCT l.id) as like_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN likes l ON p.id = l.post_id
            WHERE p.user_id = ?
            GROUP BY p.id
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    // Check if two users are friends (accepted)
    public function isFriend($userA, $userB) {
        $stmt = $this->pdo->prepare("SELECT id FROM friendships WHERE ((user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)) AND status = 'accepted'");
        $stmt->execute([$userA, $userB, $userB, $userA]);
        return $stmt->fetch() !== false;
    }

    // Get posts for a profile, filtered by viewer permissions
    public function getPostsForProfile($ownerId, $viewerId, $limit = 50, $offset = 0) {
        // If viewer is owner, return all posts
        if ($ownerId === $viewerId) {
            $stmt = $this->pdo->prepare("
                SELECT p.*, u.first_name, u.last_name, u.avatar, COUNT(DISTINCT l.id) as like_count
                FROM posts p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN likes l ON p.id = l.post_id
                WHERE p.user_id = ?
                GROUP BY p.id
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$ownerId, $limit, $offset]);
            return $stmt->fetchAll();
        }

        // If viewer is friend, allow public and friends
        if ($this->isFriend($ownerId, $viewerId)) {
            $stmt = $this->pdo->prepare("
                SELECT p.*, u.first_name, u.last_name, u.avatar, COUNT(DISTINCT l.id) as like_count
                FROM posts p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN likes l ON p.id = l.post_id
                WHERE p.user_id = ? AND p.privacy IN ('public','friends')
                GROUP BY p.id
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$ownerId, $limit, $offset]);
            return $stmt->fetchAll();
        }

        // Otherwise, only public posts
        $stmt = $this->pdo->prepare("
            SELECT p.*, u.first_name, u.last_name, u.avatar, COUNT(DISTINCT l.id) as like_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN likes l ON p.id = l.post_id
            WHERE p.user_id = ? AND p.privacy = 'public'
            GROUP BY p.id
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$ownerId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    public $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    // User functions
    public function createUser($email, $password, $firstName, $lastName) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("
            INSERT INTO users (email, password, first_name, last_name)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$email, $hashedPassword, $firstName, $lastName]);
    }
    
    public function getUserByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function verifyPassword($email, $password) {
        $user = $this->getUserByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
    
    // Post functions
    public function createPost($userId, $content, $mediaUrl = null, $privacy = 'public') {
        $stmt = $this->pdo->prepare("
            INSERT INTO posts (user_id, content, media_url, privacy) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $content, $mediaUrl, $privacy]);
    }
    
    public function getPosts($limit = 20, $offset = 0) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, u.first_name, u.last_name, u.avatar, COUNT(DISTINCT l.id) as like_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN likes l ON p.id = l.post_id
            WHERE p.privacy = 'public'
            GROUP BY p.id
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function getPostById($id) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, u.first_name, u.last_name, u.avatar, COUNT(DISTINCT l.id) as like_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN likes l ON p.id = l.post_id
            WHERE p.id = ?
            GROUP BY p.id
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Remember Me token functions
    public function createRememberMeToken($userId, $selector, $hashedToken, $expires) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO auth_tokens (user_id, selector, token, expires) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$userId, $selector, $hashedToken, $expires]);
    }

    public function verifyRememberMeToken($token) {
        if (strpos($token, ':') === false) {
            return false;
        }
        list($selector, $validator) = explode(':', $token, 2);

        $stmt = $this->pdo->prepare("SELECT * FROM auth_tokens WHERE selector = ? AND expires >= NOW()");
        $stmt->execute([$selector]);
        $authToken = $stmt->fetch();

        if ($authToken && hash_equals($authToken['token'], hash('sha256', $validator))) {
            return $this->getUserById($authToken['user_id']);
        }
        return false;
    }

    public function deleteRememberMeToken($selector) {
        $stmt = $this->pdo->prepare("DELETE FROM auth_tokens WHERE selector = ?");
        return $stmt->execute([$selector]);
    }

    // Like functions
    public function toggleLike($userId, $postId) {
        // Check if already liked
        $stmt = $this->pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$userId, $postId]);
        
        if ($stmt->fetch()) {
            // Unlike
            $stmt = $this->pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
            return $stmt->execute([$userId, $postId]);
        } else {
            // Like
            $stmt = $this->pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
            return $stmt->execute([$userId, $postId]);
        }
    }
    
    public function isLiked($userId, $postId) {
        $stmt = $this->pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$userId, $postId]);
        return $stmt->fetch() !== false;
    }


    // Friend functions
    public function getFriends($userId) {
        $stmt = $this->pdo->prepare("
            SELECT u.* FROM users u
            JOIN friendships f ON (
                (f.user1_id = ? AND f.user2_id = u.id) OR 
                (f.user2_id = ? AND f.user1_id = u.id)
            )
            WHERE f.status = 'accepted' AND u.id != ?
        ");
        $stmt->execute([$userId, $userId, $userId]);
        return $stmt->fetchAll();
    }
    
    public function getContacts($userId) {
        $stmt = $this->pdo->prepare("
            SELECT u.* FROM users u
            WHERE u.id != ?
            ORDER BY u.first_name, u.last_name
            LIMIT 20
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Friendship status between two users: 'none', 'pending', 'accepted'
    public function getFriendshipStatus($a, $b) {
        $stmt = $this->pdo->prepare("SELECT status FROM friendships WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?) LIMIT 1");
        $stmt->execute([$a,$b,$b,$a]);
        $row = $stmt->fetch();
        if (!$row) return 'none';
        return $row['status'];
    }



    // Pending friend requests (requests received by the given user)
    public function getPendingFriendRequests($userId) {
        $stmt = $this->pdo->prepare("
            SELECT f.user1_id as from_user_id, u.first_name, u.last_name, u.avatar, f.created_at
            FROM friendships f
            JOIN users u ON u.id = f.user1_id
            WHERE f.user2_id = ? AND f.status = 'pending'
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Accept a friend request (set status to 'accepted')
    public function acceptFriendRequest($fromUserId, $toUserId) {
        $stmt = $this->pdo->prepare("UPDATE friendships SET status = 'accepted' WHERE user1_id = ? AND user2_id = ? AND status = 'pending'");
        return $stmt->execute([$fromUserId, $toUserId]);
    }

    // Decline (or remove) a pending friend request
    public function declineFriendRequest($fromUserId, $toUserId) {
        $stmt = $this->pdo->prepare("DELETE FROM friendships WHERE user1_id = ? AND user2_id = ? AND status = 'pending'");
        return $stmt->execute([$fromUserId, $toUserId]);
    }

    // Email verification functions
    public function verifyEmail($token) {
        $stmt = $this->pdo->prepare("UPDATE users SET email_verified = TRUE, email_verification_token = NULL WHERE email_verification_token = ?");
        return $stmt->execute([$token]);
    }

    public function getUserByVerificationToken($token) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email_verification_token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    // Get friend count for a user
    public function getFriendCount($userId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as friend_count FROM friendships
            WHERE ((user1_id = ? AND user2_id != ?) OR (user2_id = ? AND user1_id != ?))
            AND status = 'accepted'
        ");
        $stmt->execute([$userId, $userId, $userId, $userId]);
        $result = $stmt->fetch();
        return $result['friend_count'];
    }

    // Update user profile with new fields
    public function updateUserProfile($userId, $data) {
        $fields = [];
        $values = [];

        if (isset($data['avatar'])) {
            $fields[] = 'avatar = ?';
            $values[] = $data['avatar'];
        }
        if (isset($data['bio'])) {
            $fields[] = 'bio = ?';
            $values[] = $data['bio'];
        }
        if (isset($data['hobbies'])) {
            $fields[] = 'hobbies = ?';
            $values[] = $data['hobbies'];
        }
        if (isset($data['social_links'])) {
            $fields[] = 'social_links = ?';
            $values[] = json_encode($data['social_links']);
        }
        if (isset($data['language'])) {
            $fields[] = 'language = ?';
            $values[] = $data['language'];
        }

        if (empty($fields)) return false;

        $values[] = $userId;
        $stmt = $this->pdo->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($values);
    }

    // Google login methods
    public function getUserByGoogleId($google_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE google_id = ?");
        $stmt->execute([$google_id]);
        return $stmt->fetch();
    }

    public function updateUserGoogleId($user_id, $google_id) {
        $stmt = $this->pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
        return $stmt->execute([$google_id, $user_id]);
    }

    public function createUserFromGoogle($google_id, $email, $first_name, $last_name) {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (google_id, email, first_name, last_name, email_verified, password)
            VALUES (?, ?, ?, ?, TRUE, NULL)
        ");
        if ($stmt->execute([$google_id, $email, $first_name, $last_name])) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    // Saved posts methods
    public function toggleSavePost($userId, $postId) {
        // Check if already saved
        $stmt = $this->pdo->prepare("SELECT id FROM saved_posts WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$userId, $postId]);

        if ($stmt->fetch()) {
            // Unsave
            $stmt = $this->pdo->prepare("DELETE FROM saved_posts WHERE user_id = ? AND post_id = ?");
            return $stmt->execute([$userId, $postId]);
        } else {
            // Save
            $stmt = $this->pdo->prepare("INSERT INTO saved_posts (user_id, post_id) VALUES (?, ?)");
            return $stmt->execute([$userId, $postId]);
        }
    }

    public function isPostSaved($userId, $postId) {
        $stmt = $this->pdo->prepare("SELECT id FROM saved_posts WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$userId, $postId]);
        return $stmt->fetch() !== false;
    }

    public function getSavedPosts($userId, $limit = 20, $offset = 0) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, u.first_name, u.last_name, u.avatar, COUNT(DISTINCT l.id) as like_count
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN likes l ON p.id = l.post_id
            JOIN saved_posts s ON p.id = s.post_id
            WHERE s.user_id = ?
            GROUP BY p.id
            ORDER BY s.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    public function getSuggestedFriends($userId, $limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT u.* FROM users u
            WHERE u.id != ? AND u.id NOT IN (
                SELECT f.user1_id FROM friendships f WHERE f.user2_id = ?
                UNION
                SELECT f.user2_id FROM friendships f WHERE f.user1_id = ?
            )
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->execute([$userId, $userId, $userId, $limit]);
        return $stmt->fetchAll();
    }
}
?>
