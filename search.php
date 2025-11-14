<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Page</title>
    <style>
        /* CSS Variables for the theme */
        :root {
            --primary-bg-color: #1a1a1a;
            --secondary-bg-color: #2c2c2c;
            --text-color: #ffffff;
            --secondary-text-color: #b0b0b0;
            --border-color: #444444;
            --accent-color: #007bff; /* Use this for interactive elements like hover effects or buttons */
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: var(--primary-bg-color);
            color: var(--text-color);
        }
        .search-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        ;
        }
        #searchInput {
            width: 90vw;
            max-width: 500px;
            padding: 12px 20px;
            border: 1px solid var(--border-color);
            border-radius: 25px;
            font-size: 16px;
            outline: none;
            background-color: var(--secondary-bg-color);
            color: var(--text-color);
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        #searchInput::placeholder {
            color: var(--secondary-text-color);
        }
        .results-container {
            max-width: 700px;
            margin: auto;
            background-color: var(--secondary-bg-color);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            padding: 20px;
        }
        .result-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .result-item:hover {
            background-color: #383838;
        }
        .result-item:last-child {
            border-bottom: none;
        }
        .result-item img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }
        .result-content {
            flex-grow: 1;
        }
        .result-title {
            font-weight: bold;
            color: var(--text-color);
            font-size: 18px;
        }
        .result-type {
            font-size: 12px;
            color: var(--secondary-text-color);
            text-transform: uppercase;
        }
        .result-meta {
            font-size: 14px;
            color: var(--secondary-text-color);
            margin-top: 5px;
        }
        .result-text {
            color: var(--text-color);
        }
        .no-results {
            text-align: center;
            color: var(--secondary-text-color);
            padding: 20px;
        }
        .result-actions {
            margin-left: 15px;
        }
        .add-friend-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            background-color: var(--accent-color);
            color: white;
            cursor: pointer;
            font-size: 14px;
            white-space: nowrap;
        }
        .add-friend-btn:disabled {
            background-color: #555;
            color: #aaa;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

    <div class="search-container">
        <input type="text" id="searchInput" placeholder="Search users or posts...">
    </div>

    <div class="results-container" id="resultsContainer">
        <div class="no-results" id="initialMessage">Start typing to see results.</div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('searchInput');
            const resultsContainer = document.getElementById('resultsContainer');
            let timeout = null;

            function addFriend(userId, button) {
                button.disabled = true;
                button.textContent = 'Sending...';
                fetch('send_friend_request.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                    body: 'to_user_id=' + userId + '&ajax=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        button.textContent = 'Request Sent';
                    } else {
                        alert('Error: ' + (data.message || 'Could not send friend request'));
                        button.textContent = 'Add Friend';
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    alert('Error: Could not send friend request');
                    console.error('Friend request error:', error);
                    button.textContent = 'Add Friend';
                    button.disabled = false;
                });
            }

            searchInput.addEventListener('keyup', (e) => {
                clearTimeout(timeout);

                const query = e.target.value.trim();

                if (query.length > 0) {
                    timeout = setTimeout(() => {
                        fetchResults(query);
                    }, 300);
                } else {
                    resultsContainer.innerHTML = '<div class="no-results" id="initialMessage">Start typing to see results.</div>';
                }
            });

            async function fetchResults(query) {
                resultsContainer.innerHTML = '<div class="no-results">Loading...</div>';

                try {
                    const response = await fetch(`search_api.php?q=${encodeURIComponent(query)}`);
                    const data = await response.json();

                    if (data.success) {
                        renderResults(data.results);
                    } else {
                        resultsContainer.innerHTML = `<div class="no-results">Error: ${data.error || 'Failed to fetch results.'}</div>`;
                    }
                } catch (error) {
                    resultsContainer.innerHTML = `<div class="no-results">An error occurred. Please try again.</div>`;
                    console.error('Fetch error:', error);
                }
            }

            function renderResults(results) {
                resultsContainer.innerHTML = '';

                if (results.length === 0) {
                    resultsContainer.innerHTML = '<div class="no-results">No results found.</div>';
                    return;
                }

                results.forEach(item => {
                    const resultItem = document.createElement('div');
                    resultItem.classList.add('result-item');
                    
                    const defaultAvatar = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(item.name || item.author) + '&background=0D8ABC&color=fff&size=50';
                    let avatar = item.avatar ? item.avatar : defaultAvatar;

                    let contentHtml = '';
                    if (item.type === 'user') {
                        resultItem.dataset.url = `profile.php?id=${item.id}`;
                        contentHtml = `
                            <img src="${avatar}" alt="${item.name}'s avatar">
                            <div class="result-content">
                                <div class="result-type">User</div>
                                <div class="result-title">${item.name}</div>
                                <div class="result-meta">${item.bio ? item.bio : 'No bio available.'}</div>
                            </div>
                            <div class="result-actions">
                                <button class="add-friend-btn" data-user-id="${item.id}">Add Friend</button>
                            </div>
                        `;
                    } else if (item.type === 'post') {
                        resultItem.dataset.url = `view_post.php?id=${item.id}`;
                        contentHtml = `
                            <img src="${avatar}" alt="${item.author}'s avatar">
                            <div class="result-content">
                                <div class="result-type">Post</div>
                                <div class="result-title">${item.author}</div>
                                <div class="result-meta">${item.date}</div>
                                <div class="result-text">${item.content.substring(0, 150)}${item.content.length > 150 ? '...' : ''}</div>
                            </div>
                        `;
                    }
                    resultItem.innerHTML = contentHtml;
                    resultsContainer.appendChild(resultItem);
                });
            }

            resultsContainer.addEventListener('click', (e) => {
                if (e.target.classList.contains('add-friend-btn')) {
                    e.stopPropagation();
                    addFriend(e.target.dataset.userId, e.target);
                } else if (e.target.closest('.result-item')?.dataset.url) {
                    window.location.href = e.target.closest('.result-item').dataset.url;
                }
            });
        });
    </script>
    

</body>
</html>
         
    </script>
    

</body>
</html>
