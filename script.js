// A global helper function that can be used across different parts of the script.
function addFriend(userId, button) {
    fetch('send_friend_request.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'to_user_id=' + userId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (button) {
                button.textContent = 'Request Sent';
                button.disabled = true;
            }
        } else {
            alert('Error: ' + (data.error || 'Could not send friend request'));
        }
    })
    .catch(error => {
        alert('Error: Could not send friend request');
        console.error('Friend request error:', error);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Search functionality
    const searchInput = document.getElementById('searchInput');

    // Chat overlay elements
    const chatBtn = document.getElementById('chatBtn');
    const chatOverlay = document.getElementById('chatOverlay');
    const closeChatBtn = document.getElementById('closeChat');
    const chatFriendsList = document.getElementById('chatFriendsList');
    const chatMessagesHeader = document.getElementById('chatMessagesHeader');
    const chatMessages = document.getElementById('chatMessages');
    const chatInputForm = document.getElementById('chatInputForm');
    const chatMessageInput = document.getElementById('chatMessageInput');
    const chatEmojiBtn = document.getElementById('chatEmojiBtn');
    const chatEmojiPicker = document.getElementById('chatEmojiPicker');

    let selectedFriendId = null;
    let pollInterval = null;
    let searchTimeout = null;

    // --- Search Page Functionality ---
    function performSearch(query) {
        const searchResults = document.querySelector('.search-results');
        if (!searchResults) return;

        if (!query.trim()) {
            searchResults.innerHTML = '<p>Type in the box above to start searching.</p>';
            return;
        }

        searchResults.innerHTML = '<p>Searching...</p>';

        fetch('search_api.php?q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.results.length === 0) {
                        searchResults.innerHTML = '<p>No results found for "' + query + '"</p>';
                        return;
                    }

                    let html = '';
                    data.results.forEach(result => {
                        if (result.type === 'user') {
                            const avatarUrl = result.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(result.name) + '&background=0D8ABC&color=fff&size=40';
                            const bio = result.bio || 'No bio';
                            html += '<div class="search-result-item" role="button" tabindex="0" onclick="window.location.href=\'profile.php?id=' + result.id + '\'">';
                            html += '<div class="search-result-avatar">';
                            html += '<img src="' + avatarUrl + '" alt="Avatar" />';
                            html += '</div>';
                            html += '<div class="search-result-body">';
                            html += '<div class="search-result-title">' + result.name + '</div>';
                            html += '<div class="search-result-sub">' + bio + '</div>';
                            html += '</div>';
                            html += '<div class="search-result-actions">';
                            html += '<button class="search-add-friend-btn" data-user-id="' + result.id + '">Add Friend</button>';
                            html += '</div>';
                            html += '</div>';
                        } else if (result.type === 'post') {
                            const avatarUrl = result.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(result.author) + '&background=0D8ABC&color=fff&size=40';
                            let contentPreview = result.content;
                            if (contentPreview.length > 100) {
                                contentPreview = contentPreview.substring(0, 100) + '...';
                            }
                            html += '<div class="search-result-item" data-url="view_post.php?id=' + result.id + '" role="button" tabindex="0">';
                            html += '<div class="search-result-avatar">';
                            html += '<img src="' + avatarUrl + '" alt="Avatar" />';
                            html += '</div>';
                            html += '<div class="search-result-body">';
                            html += '<div class="search-result-title">' + result.author + '</div>';
                            html += '<div class="search-result-sub">' + contentPreview + '</div>';
                            html += '<div class="search-result-date">' + result.date + '</div>';
                            html += '</div>';
                            html += '</div>';
                        }
                    });
                    searchResults.innerHTML = html;
                } else {
                    searchResults.innerHTML = '<p>Error: ' + (data.error || 'Search failed') + '</p>';
                }
            })
            .catch(error => {
                searchResults.innerHTML = '<p>Error: Could not perform search</p>';
                console.error('Search error:', error);
            });
    }

    // Search button and input event listeners
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const query = searchInput.value.trim();
            if (query.length > 0) {
                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 300);
            } else {
                const searchResults = document.querySelector('.search-results');
                if (searchResults) {
                    searchResults.innerHTML = '<p>Type in the box above to start searching.</p>';
                }
            }
        });
    }

    const searchSubmitBtn = document.getElementById('searchSubmitBtn');
    if (searchSubmitBtn) {
        searchSubmitBtn.addEventListener('click', () => {
            const query = searchInput ? searchInput.value.trim() : '';
            if (query.length > 0) {
                performSearch(query);
            }
        });
    }

    // Event delegation for search results
    const searchResultsContainer = document.querySelector('.search-results');
    if (searchResultsContainer) {
        searchResultsContainer.addEventListener('click', (e) => {
            const target = e.target;
            if (target.matches('.search-add-friend-btn')) {
                e.stopPropagation();
                const userId = target.dataset.userId;
                addFriend(userId, target);
            } else {
                const resultItem = target.closest('.search-result-item');
                if (resultItem && resultItem.dataset.url) {
                    window.location.href = resultItem.dataset.url;
                }
            }
        });
    }

    // --- Chat functionality ---
    // Added conditional checks for all chat-related elements
    if (chatBtn && chatOverlay && closeChatBtn && chatFriendsList && chatMessagesHeader && chatMessages && chatInputForm && chatMessageInput && chatEmojiBtn && chatEmojiPicker) {

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function loadMessages() {
            if (!selectedFriendId) return;
            fetch('fetch_messages.php?user_id=' + selectedFriendId)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        chatMessages.innerHTML = '';
                        const currentUserId = window.currentUserId ? parseInt(window.currentUserId, 10) : null;
                        data.messages.forEach(msg => {
                            const isSent = msg.sender_id === currentUserId;
                            const div = document.createElement('div');
                            div.classList.add('message');
                            div.classList.add(isSent ? 'sent' : 'received');
                            
                            const contentDiv = document.createElement('div');
                            contentDiv.className = 'message-content';
                            contentDiv.textContent = msg.message;
                            div.appendChild(contentDiv);
                            if (isSent) {
                                const statusDiv = document.createElement('div');
                                statusDiv.className = 'message-status';
                                if (msg.is_seen) {
                                    statusDiv.textContent = 'Seen';
                                } else {
                                    statusDiv.textContent = 'Sent';
                                }
                                div.appendChild(statusDiv);
                            }
                            chatMessages.appendChild(div);
                        });
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                });
        }
        
        // This is the original line that caused the error
        // chatInputForm.addEventListener('submit', e => { ... });
        // The fix is to put this inside the conditional check above

        chatInputForm.addEventListener('submit', e => {
            e.preventDefault();
            const message = chatMessageInput.value.trim();
            if (!message || !selectedFriendId) return;
            fetch('send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'receiver_id=' + encodeURIComponent(selectedFriendId) + '&message=' + encodeURIComponent(message)
            }).then(res => res.json())
            .then(data => {
                if (data.success) {
                    chatMessageInput.value = '';
                    loadMessages();
                } else {
                    alert('Failed to send message: ' + (data.error || 'Unknown error'));
                }
            }).catch(() => alert('Failed to send message'));
        });

        chatFriendsList.querySelectorAll('.friend-item').forEach(item => {
            item.addEventListener('click', () => {
                if (pollInterval) clearInterval(pollInterval);
                if (chatFriendsList) {
                    chatFriendsList.querySelectorAll('.friend-item').forEach(i => i.classList.remove('active'));
                }
                item.classList.add('active');
                selectedFriendId = item.dataset.friendId;
                if (chatMessagesHeader) {
                    chatMessagesHeader.textContent = item.textContent.trim();
                }
                if (chatInputForm) {
                    chatInputForm.style.display = 'flex';
                }
                loadMessages();
                pollInterval = setInterval(loadMessages, 3000);
            });
        });

        chatBtn.addEventListener('click', () => {
            chatOverlay.classList.add('active');
        });

        closeChatBtn.addEventListener('click', () => {
            chatOverlay.classList.remove('active');
            if (pollInterval) clearInterval(pollInterval);
            if (chatFriendsList) {
                chatFriendsList.querySelectorAll('.friend-item').forEach(i => i.classList.remove('active'));
            }
            if (chatMessagesHeader) {
                chatMessagesHeader.textContent = 'Select a friend to start chatting';
            }
            if (chatMessages) {
                chatMessages.innerHTML = '';
            }
            if (chatInputForm) {
                chatInputForm.style.display = 'none';
            }
            selectedFriendId = null;
        });

        // Close chat on overlay click
        chatOverlay.addEventListener('click', (e) => {
            if (e.target === chatOverlay) {
                closeChatBtn.click();
            }
        });

        // Emoji picker logic for chat overlay
        chatEmojiBtn.addEventListener('click', () => {
            chatEmojiPicker.style.display = chatEmojiPicker.style.display === 'block' ? 'none' : 'block';
        });

        chatEmojiPicker.addEventListener('emoji-click', event => {
            chatMessageInput.value += event.detail.emoji.unicode;
            chatEmojiPicker.style.display = 'none';
        });

    }

    // Set current user ID globally for message sender comparison
    window.currentUserId = document.body.getAttribute('data-user-id') || null;

    // Post interaction functionality
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const postId = btn.dataset.postId;
            fetch('toggle_like.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'post_id=' + postId
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    btn.innerHTML = data.liked ? '👍 Liked (' + data.like_count + ')' : '👍 Like (' + data.like_count + ')';
                    btn.setAttribute('aria-pressed', data.liked);
                }
            });
        });
    });

    // Post menu functionality
    document.querySelectorAll('.post__menu-btn').forEach(btn => {
        btn.addEventListener('click', (event) => {
            event.stopPropagation(); // Prevent event bubbling
            const menu = btn.nextElementSibling;
            const isExpanded = btn.getAttribute('aria-expanded') === 'true';

            // Close any other open menus
            document.querySelectorAll('.post__menu').forEach(m => {
                if (m !== menu) {
                    m.classList.remove('active');
                    const siblingBtn = m.previousElementSibling;
                    if (siblingBtn && siblingBtn.classList.contains('post__menu-btn')) {
                        siblingBtn.setAttribute('aria-expanded', 'false');
                    }
                }
            });

            if (!isExpanded) {
                menu.classList.add('active');
                btn.setAttribute('aria-expanded', 'true');
            } else {
                menu.classList.remove('active');
                btn.setAttribute('aria-expanded', 'false');
            }
        });
    });

    // Close menus when clicking outside
    document.addEventListener('click', () => {
        document.querySelectorAll('.post__menu').forEach(menu => {
            menu.classList.remove('active');
        });
        document.querySelectorAll('.post__menu-btn').forEach(btn => {
            btn.setAttribute('aria-expanded', 'false');
        });
    });

    // Post menu actions
    document.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', (event) => {
            event.stopPropagation(); // Prevent event bubbling to document
            const action = item.dataset.action;
            const postId = item.closest('.post').dataset.postId;
            const menu = item.closest('.post__menu');
            const btn = menu.previousElementSibling;

            if (action === 'copy-link') {
                const url = window.location.origin + '/view_post.php?id=' + postId;
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link copied to clipboard!');
                });
            } else if (action === 'save') {
                // Save post functionality
                fetch('save_post.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'post_id=' + postId
                }).then(res => res.json()).then(data => {
                    if (data.success) {
                        alert(data.message);
                    } else {
                        alert('Error: ' + (data.error || 'Could not save post'));
                    }
                }).catch(() => alert('Error: Could not save post'));
            } else if (action === 'view') {
                window.location.href = 'view_post.php?id=' + postId;
            }

            // Close the menu
            menu.classList.remove('active');
            btn.setAttribute('aria-expanded', 'false');
        });
    });

    // Fix for post menu button click not toggling menu properly
    document.querySelectorAll('.post__menu-btn').forEach(btn => {
        btn.removeEventListener('click', btn._postMenuClickHandler);
        btn._postMenuClickHandler = function(event) {
            event.stopPropagation();
            const menu = btn.nextElementSibling;
            const isExpanded = btn.getAttribute('aria-expanded') === 'true';

            // Close other menus
            document.querySelectorAll('.post__menu').forEach(m => {
                if (m !== menu) {
                    m.classList.remove('active');
                    const siblingBtn = m.previousElementSibling;
                    if (siblingBtn && siblingBtn.classList.contains('post__menu-btn')) {
                        siblingBtn.setAttribute('aria-expanded', 'false');
                    }
                }
            });

            if (!isExpanded) {
                menu.classList.add('active');
                btn.setAttribute('aria-expanded', 'true');
            } else {
                menu.classList.remove('active');
                btn.setAttribute('aria-expanded', 'false');
            }
        };
        btn.addEventListener('click', btn._postMenuClickHandler);
    });

    // Close menus when clicking outside (fix for menu not closing properly)
    document.addEventListener('click', (event) => {
        const openMenus = document.querySelectorAll('.post__menu.active');
        openMenus.forEach(menu => {
            if (!menu.contains(event.target)) {
                menu.classList.remove('active');
                const btn = menu.previousElementSibling;
                if (btn && btn.classList.contains('post__menu-btn')) {
                    btn.setAttribute('aria-expanded', 'false');
                }
            }
        });
    });

    // Make post content clickable to navigate to view_post.php
    document.querySelectorAll('.post-clickable-area').forEach(area => {
        area.addEventListener('click', function(e) {
            // Don't navigate if clicking on a link, button, or interactive element inside the content
            if (e.target.closest('a, button, input, select, textarea, video')) {
                return;
            }
            const postUrl = this.dataset.href;
            if (postUrl) {
                window.location.href = postUrl;
            }
        });
    });

    // Use event delegation for all post interactions to make them more robust.
    document.body.addEventListener('click', function(e) {
        const target = e.target;
        // This function is currently empty but is a good practice for future event handling
        // For example, you could handle all 'like' clicks here:
        // if (target.matches('.like-btn')) {
        //   handleLike(target);
        // }
    });

    // Hide loader on page load
    window.addEventListener('load', () => {
        const globalLoader = document.getElementById('globalLoader');
        if (globalLoader) {
            globalLoader.style.display = 'none';
        }
    });

    // Theme switching
    const themeSelect = document.getElementById('themeSelect');
    if (themeSelect) {
        themeSelect.addEventListener('change', (e) => {
            const theme = e.target.value;
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
        });
    }

    // Language selection
    const languageSelect = document.getElementById('languageSelect');
    if (languageSelect) {
        languageSelect.addEventListener('change', (e) => {
            const language = e.target.value;
            localStorage.setItem('language', language);
            // In a real app, you would reload the page or update content dynamically
            alert('Language changed to ' + language + '. Please refresh the page to apply changes.');
        });
    }

    // Notifications toggle
    const notificationsToggle = document.getElementById('notificationsToggle');
    if (notificationsToggle) {
        notificationsToggle.addEventListener('change', (e) => {
            const enabled = e.target.checked;
            localStorage.setItem('notifications', enabled);
            if (enabled) {
                // Request notification permission
                if ('Notification' in window) {
                    Notification.requestPermission();
                }
            }
        });
    }

    // Font size adjustment
    const fontSizeSlider = document.getElementById('fontSizeSlider');
    const fontSizeValue = document.getElementById('fontSizeValue');
    if (fontSizeSlider && fontSizeValue) {
        fontSizeSlider.addEventListener('input', (e) => {
            const size = e.target.value;
            fontSizeValue.textContent = size + 'px';
            document.documentElement.style.fontSize = size + 'px';
            localStorage.setItem('fontSize', size);
        });
    }

    // Brightness adjustment
    const brightnessSlider = document.getElementById('brightnessSlider');
    const brightnessValue = document.getElementById('brightnessValue');
    if (brightnessSlider && brightnessValue) {
        brightnessSlider.addEventListener('input', (e) => {
            const brightness = e.target.value;
            brightnessValue.textContent = brightness + '%';
            document.documentElement.style.filter = `brightness(${brightness}%)`;
            localStorage.setItem('brightness', brightness);
        });
    }

    // Fullscreen toggle
    const fullscreenToggle = document.getElementById('fullscreenToggle');
    if (fullscreenToggle) {
        fullscreenToggle.addEventListener('change', (e) => {
            if (e.target.checked) {
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        });
    }

    // Account actions
    const editProfileBtn = document.getElementById('editProfileBtn');
    if (editProfileBtn) {
        editProfileBtn.addEventListener('click', () => {
            window.location.href = 'edit_profile.php';
        });
    }

    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        });
    }

    const deleteAccountBtn = document.getElementById('deleteAccountBtn');
    if (deleteAccountBtn) {
        deleteAccountBtn.addEventListener('click', () => {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                // In a real app, you would make an API call to delete the account
                alert('Account deletion functionality would be implemented here.');
            }
        });
    }

    const changePasswordBtn = document.getElementById('changePasswordBtn');
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', () => {
            // In a real app, you would open a password change modal
            alert('Password change functionality would be implemented here.');
        });
    }

    const managePermissionsBtn = document.getElementById('managePermissionsBtn');
    if (managePermissionsBtn) {
        managePermissionsBtn.addEventListener('click', () => {
            // In a real app, you would open a permissions management modal
            alert('Permissions management functionality would be implemented here.');
        });
    }

    const privacyPolicyLink = document.getElementById('privacyPolicyLink');
    if (privacyPolicyLink) {
        privacyPolicyLink.addEventListener('click', (e) => {
            e.preventDefault();
            // In a real app, you would open the privacy policy page
            alert('Privacy policy would be displayed here.');
        });
    }

    // Load saved settings on page load
    function loadSettings() {
        const themeSelect = document.getElementById('themeSelect');
        const languageSelect = document.getElementById('languageSelect');
        const notificationsToggle = document.getElementById('notificationsToggle');
        const fontSizeSlider = document.getElementById('fontSizeSlider');
        const fontSizeValue = document.getElementById('fontSizeValue');
        const brightnessSlider = document.getElementById('brightnessSlider');
        const brightnessValue = document.getElementById('brightnessValue');

        // Theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (themeSelect) themeSelect.value = savedTheme;
        document.documentElement.setAttribute('data-theme', savedTheme);

        // Language
        const savedLanguage = localStorage.getItem('language') || 'en';
        if (languageSelect) languageSelect.value = savedLanguage;

        // Notifications
        const savedNotifications = localStorage.getItem('notifications') === 'true';
        if (notificationsToggle) notificationsToggle.checked = savedNotifications;

        // Font size
        const savedFontSize = localStorage.getItem('fontSize') || '16';
        if (fontSizeSlider) fontSizeSlider.value = savedFontSize;
        if (fontSizeValue) fontSizeValue.textContent = savedFontSize + 'px';
        document.documentElement.style.fontSize = savedFontSize + 'px';

        // Brightness
        const savedBrightness = localStorage.getItem('brightness') || '100';
        if (brightnessSlider) brightnessSlider.value = savedBrightness;
        if (brightnessValue) brightnessValue.textContent = savedBrightness + '%';
        document.documentElement.style.filter = `brightness(${savedBrightness}%)`;
    }

    // Load settings when DOM is ready
    loadSettings();

    // Video scroll pause functionality
    let scrollTimeout;
    window.addEventListener('scroll', () => {
        // Clear previous timeout
        clearTimeout(scrollTimeout);

        // Pause all videos when scrolling starts
        document.querySelectorAll('video').forEach(video => {
            if (!video.paused) {
                video.pause();
            }
        });

        // Set timeout to allow play after scrolling stops
        scrollTimeout = setTimeout(() => {
            // Videos can be played again after scrolling stops
        }, 150);
    });
});