/* Main JavaScript for Instagram Clone */

document.addEventListener('DOMContentLoaded', function() {
    // Like animation
    const likeButtons = document.querySelectorAll('.like-btn');
    if (likeButtons) {
        likeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const postId = this.dataset.postId;
                const likeIcon = this.querySelector('i');
                
                // Toggle the liked class
                likeIcon.classList.toggle('fas');
                likeIcon.classList.toggle('far');
                likeIcon.classList.toggle('liked');
                
                // Add animation class
                likeIcon.classList.add('like-animation');
                
                // Remove animation class after animation completes
                setTimeout(() => {
                    likeIcon.classList.remove('like-animation');
                }, 300);
                
                // Send AJAX request to like/unlike
                fetch('ajax/like_post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'post_id=' + postId
                })
                .then(response => response.json())
                .then(data => {
                    // Update like count
                    const likeCountElement = document.querySelector(`.like-count-${postId}`);
                    if (likeCountElement) {
                        likeCountElement.textContent = data.likes_count + ' likes';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
    }
    
    // Double-click to like
    const postImages = document.querySelectorAll('.post-image');
    if (postImages) {
        postImages.forEach(image => {
            image.addEventListener('dblclick', function() {
                const postId = this.dataset.postId;
                const likeButton = document.querySelector(`.like-btn[data-post-id="${postId}"]`);
                
                if (likeButton) {
                    // Show heart animation on image
                    const heartOverlay = document.createElement('div');
                    heartOverlay.classList.add('heart-overlay');
                    heartOverlay.innerHTML = '<i class="fas fa-heart"></i>';
                    this.parentNode.appendChild(heartOverlay);
                    
                    // Remove heart overlay after animation
                    setTimeout(() => {
                        heartOverlay.remove();
                    }, 1000);
                    
                    // Click the like button
                    likeButton.click();
                }
            });
        });
    }
    
    // Comment form submission
    const commentForms = document.querySelectorAll('.comment-form');
    if (commentForms) {
        commentForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const postId = this.dataset.postId;
                const commentInput = this.querySelector('.comment-input');
                const comment = commentInput.value.trim();
                
                if (comment) {
                    // Send AJAX request to add comment
                    fetch('ajax/add_comment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'post_id=' + postId + '&comment=' + encodeURIComponent(comment)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Clear input
                            commentInput.value = '';
                            
                            // Update comment count or append new comment if on single post page
                            const commentsContainer = document.querySelector('.comments-container');
                            if (commentsContainer) {
                                // Create new comment element
                                const newComment = document.createElement('div');
                                newComment.classList.add('comment');
                                newComment.innerHTML = `
                                    <img src="uploads/profile_pics/${data.user.profile_pic}" alt="${data.user.username}" class="comment-user-pic">
                                    <div class="comment-content">
                                        <div>
                                            <span class="comment-username">${data.user.username}</span>
                                            <span class="comment-text">${data.comment.comment}</span>
                                        </div>
                                        <div class="comment-time">Just now</div>
                                    </div>
                                `;
                                commentsContainer.appendChild(newComment);
                            } else {
                                // Update comment count link
                                const commentCountLink = document.querySelector(`.comment-count-${postId}`);
                                if (commentCountLink) {
                                    const count = parseInt(data.comments_count);
                                    commentCountLink.textContent = `View all ${count} comments`;
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }
            });
        });
    }
    
    // Follow/Unfollow button
    const followBtn = document.querySelector('.follow-btn');
    if (followBtn) {
        followBtn.addEventListener('click', function() {
            const username = this.dataset.username;
            const isFollowing = this.classList.contains('following');
            
            // Update button state
            if (isFollowing) {
                this.textContent = 'Follow';
                this.classList.remove('following');
                this.classList.remove('btn-secondary');
                this.classList.add('btn-primary');
            } else {
                this.textContent = 'Following';
                this.classList.add('following');
                this.classList.remove('btn-primary');
                this.classList.add('btn-secondary');
            }
            
            // Send AJAX request to follow/unfollow
            fetch('ajax/follow_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'username=' + username
            })
            .then(response => response.json())
            .then(data => {
                // Update follower count
                const followerCountElement = document.querySelector('.follower-count');
                if (followerCountElement) {
                    followerCountElement.textContent = data.followers_count;
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
    
    // Image preview for create post
    const imageInput = document.getElementById('image');
    const previewContainer = document.querySelector('.create-post-preview');
    const previewPlaceholder = document.querySelector('.create-post-placeholder');
    
    if (imageInput && previewContainer) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    // Clear placeholder
                    if (previewPlaceholder) {
                        previewPlaceholder.style.display = 'none';
                    }
                    
                    // Remove any existing preview
                    const existingPreview = previewContainer.querySelector('img');
                    if (existingPreview) {
                        existingPreview.remove();
                    }
                    
                    // Create new preview
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    previewContainer.appendChild(img);
                }
                
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Message scrolling
    const chatMessages = document.querySelector('.chat-messages');
    if (chatMessages) {
        // Scroll to bottom of messages
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Real-time chat (simplified version without WebSockets)
    const messageForm = document.querySelector('.chat-form');
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const receiverId = this.dataset.receiverId;
            const messageInput = this.querySelector('.chat-input');
            const message = messageInput.value.trim();
            
            if (message) {
                // Send AJAX request to add message
                fetch('ajax/send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'receiver_id=' + receiverId + '&message=' + encodeURIComponent(message)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear input
                        messageInput.value = '';
                        
                        // Add message to chat
                        const chatMessages = document.querySelector('.chat-messages');
                        if (chatMessages) {
                            const newMessage = document.createElement('div');
                            newMessage.classList.add('chat-message', 'message-sent');
                            newMessage.innerHTML = `
                                <div class="message-text">${message}</div>
                                <div class="message-time">Just now</div>
                            `;
                            chatMessages.appendChild(newMessage);
                            
                            // Scroll to bottom
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        });
    }
    
    // Simple polling for new messages
    function checkNewMessages() {
        const chatContainer = document.querySelector('.chat-container');
        if (chatContainer) {
            const receiverId = chatContainer.dataset.receiverId;
            if (receiverId) {
                fetch('ajax/check_new_messages.php?receiver_id=' + receiverId)
                .then(response => response.json())
                .then(data => {
                    if (data.new_messages && data.new_messages.length > 0) {
                        const chatMessages = document.querySelector('.chat-messages');
                        if (chatMessages) {
                            data.new_messages.forEach(message => {
                                const newMessage = document.createElement('div');
                                newMessage.classList.add('chat-message', 'message-received');
                                newMessage.innerHTML = `
                                    <div class="message-text">${message.message_text}</div>
                                    <div class="message-time">${message.formatted_time}</div>
                                `;
                                chatMessages.appendChild(newMessage);
                            });
                            
                            // Scroll to bottom
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }
    }
    
    // Check for new messages every 5 seconds
    if (document.querySelector('.chat-container')) {
        setInterval(checkNewMessages, 5000);
    }
});

// Gestion des bookmarks
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.bookmark-btn').forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const icon = this.querySelector('i');
            
            fetch('ajax/toggle_bookmark.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.classList.toggle('bookmarked');
                    icon.classList.toggle('far');
                    icon.classList.toggle('fas');
                }
            });
        });
    });
});


// Vérifier les nouvelles notifications périodiquement
function checkNewNotifications() {
    if (!document.querySelector('.notification-badge')) return;
    
    fetch('ajax/check_notifications.php')
    .then(response => response.json())
    .then(data => {
        if (data.unread_count > 0) {
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                badge.textContent = data.unread_count;
            } else {
                const link = document.querySelector('.nav-link[href="notifications.php"]');
                if (link) {
                    link.innerHTML += `<span class="notification-badge">${data.unread_count}</span>`;
                }
            }
        }
    });
}

// Vérifier toutes les 30 secondes
setInterval(checkNewNotifications, 30000);


// Vérifier les nouvelles notifications
function checkNewNotifications() {
    if (!is_logged_in) return;
    
    fetch('ajax/check_notifications.php')
    .then(response => response.json())
    .then(data => {
        if (data.unread_count > 0) {
            const badge = document.querySelector('.notification-badge');
            const notificationLink = document.querySelector('.nav-link[href="notifications.php"]');
            
            if (badge) {
                const previousCount = parseInt(badge.textContent);
                badge.textContent = data.unread_count;
                
                // Animer si nouveau nombre > ancien nombre
                if (data.unread_count > previousCount) {
                    badge.classList.add('new-notification');
                    setTimeout(() => badge.classList.remove('new-notification'), 1000);
                }
            } else if (notificationLink) {
                // Créer le badge s'il n'existe pas
                const newBadge = document.createElement('span');
                newBadge.className = 'notification-badge new-notification';
                newBadge.textContent = data.unread_count;
                notificationLink.appendChild(newBadge);
                setTimeout(() => newBadge.classList.remove('new-notification'), 1000);
            }
        } else {
            // Supprimer le badge si aucune notification non lue
            const badge = document.querySelector('.notification-badge');
            if (badge) badge.remove();
        }
    });
}

// Vérifier toutes les 30 secondes
setInterval(checkNewNotifications, 30000);

// Vérifier au chargement de la page
document.addEventListener('DOMContentLoaded', checkNewNotifications);


// Remplacer le code existant par cette version améliorée
function setupBookmarkButtons() {
    document.querySelectorAll('.bookmark-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            const icon = this.querySelector('i');
            
            fetch('ajax/toggle_bookmark.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}`
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'état visuel
                    this.classList.toggle('bookmarked');
                    icon.classList.toggle('far');
                    icon.classList.toggle('fas');
                    
                    // Ajouter une animation de feedback
                    icon.classList.add('bookmark-animation');
                    setTimeout(() => {
                        icon.classList.remove('bookmark-animation');
                    }, 500);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });
}

// Appeler cette fonction au chargement de la page
document.addEventListener('DOMContentLoaded', setupBookmarkButtons);


// Modifiez la partie SSE :
if (typeof(EventSource) !== "undefined") {
    const eventSource = new EventSource("notifications_sse.php?last_check=<?php echo time(); ?>");
    
    eventSource.onmessage = function(event) {
        if (event.data.trim() === ': heartbeat') return;
        
        try {
            const data = JSON.parse(event.data);
            if (data.new_notifications) {
                location.reload();
            }
        } catch (e) {
            console.error('Error parsing SSE data:', e);
        }
    };
    
    eventSource.onerror = function() {
        console.error('SSE error occurred');
        eventSource.close();
    };
}


// Gestion des pièces jointes
document.querySelectorAll('.attachment-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        this.querySelector('input[type="file"]').click();
    });
});

document.querySelectorAll('#message-attachment').forEach(input => {
    input.addEventListener('change', function() {
        if (this.files.length > 0) {
            // Envoyer chaque fichier séparément
            Array.from(this.files).forEach(file => {
                const formData = new FormData();
                formData.append('receiver_id', this.closest('.chat-form').dataset.receiverId);
                formData.append('attachment', file);

                fetch('ajax/send_attachment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Ajouter le message à l'interface
                        const chatMessages = document.querySelector('.chat-messages');
                        if (chatMessages) {
                            const newMessage = document.createElement('div');
                            newMessage.classList.add('chat-message', 'message-sent');
                            
                            if (data.message_type === 'image') {
                                newMessage.innerHTML = `
                                    <div class="message-attachment">
                                        <img src="uploads/messages/${data.file_path}" alt="Image">
                                    </div>
                                    <div class="message-time">Just now</div>
                                `;
                            } else if (data.message_type === 'video') {
                                newMessage.innerHTML = `
                                    <div class="message-attachment">
                                        <video controls>
                                            <source src="uploads/messages/${data.file_path}" type="${data.file_mime_type}">
                                        </video>
                                    </div>
                                    <div class="message-time">Just now</div>
                                `;
                            } else if (data.message_type === 'audio') {
                                newMessage.innerHTML = `
                                    <div class="message-attachment">
                                        <audio controls>
                                            <source src="uploads/messages/${data.file_path}" type="${data.file_mime_type}">
                                        </audio>
                                    </div>
                                    <div class="message-time">Just now</div>
                                `;
                            } else {
                                newMessage.innerHTML = `
                                    <div class="message-attachment file-attachment">
                                        <a href="uploads/messages/${data.file_path}" download="${data.file_name}">
                                            <i class="fas fa-file-download"></i>
                                            Télécharger ${data.file_name}
                                        </a>
                                    </div>
                                    <div class="message-time">Just now</div>
                                `;
                            }
                            
                            chatMessages.appendChild(newMessage);
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                        }
                    }
                });
            });
        }
    });
});

// Gestion des messages vocaux
let mediaRecorder;
let audioChunks = [];
let recordingTimer;

document.querySelectorAll('.voice-message-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const modal = document.getElementById('voice-message-modal');
        modal.style.display = 'block';
        
        startRecording();
    });
});

document.querySelector('.close-modal').addEventListener('click', function() {
    stopRecording();
    document.getElementById('voice-message-modal').style.display = 'none';
});

document.querySelector('.cancel-recording').addEventListener('click', function() {
    stopRecording();
    document.getElementById('voice-message-modal').style.display = 'none';
});

document.querySelector('.stop-recording').addEventListener('click', function() {
    stopRecording();
    sendVoiceMessage();
    document.getElementById('voice-message-modal').style.display = 'none';
});

// Dans main.js
function startRecording() {
    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(stream => {
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];
            
            mediaRecorder.ondataavailable = event => {
                audioChunks.push(event.data);
            };
            
            mediaRecorder.start(100); // Collect data every 100ms
            
            // Timer
            let seconds = 0;
            recordingTimer = setInterval(() => {
                seconds++;
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = seconds % 60;
                document.querySelector('.recording-timer').textContent = 
                    `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
            }, 1000);
        })
        .catch(error => {
            console.error('Erreur microphone:', error);
            alert('Erreur d\'accès au microphone: ' + error.message);
        });
}

function stopRecording() {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
    }
    
    if (recordingTimer) {
        clearInterval(recordingTimer);
    }
}

function sendVoiceMessage() {
    const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
    const receiverId = document.querySelector('.chat-form').dataset.receiverId;
    
    // Vérification que le blob n'est pas vide
    if (audioBlob.size === 0) {
        console.error('Le message vocal est vide');
        alert('Le message vocal est vide');
        return;
    }

    const formData = new FormData();
    formData.append('receiver_id', receiverId);
    formData.append('voice_message', audioBlob, 'voice_message.wav');

    fetch('ajax/send_voice_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Erreur réseau');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const chatMessages = document.querySelector('.chat-messages');
            if (chatMessages) {
                const audioUrl = 'uploads/messages/' + data.file_path;
                
                // Vérification que le fichier existe
                fetch(audioUrl)
                    .then(res => {
                        if (!res.ok) throw new Error('Fichier audio introuvable');
                        
                        const newMessage = document.createElement('div');
                        newMessage.classList.add('chat-message', 'message-sent');
                        newMessage.innerHTML = `
                            <div class="message-attachment">
                                <audio controls>
                                    <source src="${audioUrl}" type="${data.file_mime_type}">
                                    Votre navigateur ne supporte pas l'audio.
                                </audio>
                            </div>
                            <div class="message-time">Just now</div>
                        `;
                        chatMessages.appendChild(newMessage);
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    })
                    .catch(err => {
                        console.error('Erreur:', err);
                        alert('Le fichier audio n\'a pas pu être chargé');
                    });
            }
        } else {
            throw new Error(data.message || 'Erreur inconnue');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur: ' + error.message);
    });
}

function stopRecording() {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
    }
    
    if (recordingTimer) {
        clearInterval(recordingTimer);
    }
}

function sendVoiceMessage() {
    const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
    const receiverId = document.querySelector('.chat-form').dataset.receiverId;
    
    const formData = new FormData();
    formData.append('receiver_id', receiverId);
    formData.append('voice_message', audioBlob, 'voice_message.wav');
    
    fetch('ajax/send_voice_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Erreur réseau');
        return response.json();
    })
    .then(data => {
        console.log('Réponse audio:', data); // Debug
        if (data.success) {
            const chatMessages = document.querySelector('.chat-messages');
            if (chatMessages) {
                const newMessage = document.createElement('div');
                newMessage.classList.add('chat-message', 'message-sent');
                newMessage.innerHTML = `
                    <div class="message-attachment">
                        <audio controls>
                            <source src="uploads/messages/${data.file_path}" type="audio/wav">
                        </audio>
                    </div>
                    <div class="message-time">Just now</div>
                `;
                chatMessages.appendChild(newMessage);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        } else {
            console.error('Erreur:', data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}