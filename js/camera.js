export const CameraModule = {
    mediaStream: null,
    photoFile: null,
    photoPreview: null,
    messageText: '',

    action_message() {
        const popupContent = `
            <div class="camera-popup">
                <video id="camera" autoplay playsinline></video>
                <canvas id="canvas" style="display:none;"></canvas>
                <div class="camera-controls">
                    <button class="capture-btn" @click="capturePhoto()">
                        <i class="fas fa-camera"></i>
                    </button>
                    <button class="close-btn" @click="closeCamera()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
    
        this.showPopup(popupContent);
        this.startCamera();
    },
    
    async startCamera() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { 
                    facingMode: 'environment',
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                },
                audio: false
            });
            
            const video = document.getElementById('camera');
            video.srcObject = stream;
            this.mediaStream = stream;
        } catch (error) {
            console.error('Error accessing camera:', error);
            alert('Unable to access camera');
            this.removePopup();
        }
    },
    
    capturePhoto() {
        const video = document.getElementById('camera');
        const canvas = document.getElementById('canvas');
        const context = canvas.getContext('2d');
    
        // Ajuster la taille du canvas à celle de la vidéo
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        // Capturer l'image
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Arrêter la caméra
        this.closeCamera();
    
        // Convertir en fichier et afficher le formulaire de commentaire
        canvas.toBlob((blob) => {
            const file = new File([blob], "photo.jpg", { type: "image/jpeg" });
            this.showCommentForm(file);
        }, 'image/jpeg', 0.9);
    },
    
    closeCamera() {
        if (this.mediaStream) {
            this.mediaStream.getTracks().forEach(track => track.stop());
        }
        this.removePopup();
    },
    
    showCommentForm(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            this.photoFile = file;
            this.photoPreview = e.target.result;
            
            const formContent = `
                <div class="comment-popup">
                    <div class="preview">
                        <img src="${this.photoPreview}" alt="Preview">
                    </div>
                    <textarea 
                        placeholder="Add a comment..." 
                        maxlength="500"
                        @input="messageText = $el.value"
                        autofocus
                    ></textarea>
                    <div class="buttons">
                        <button @click="sendMessage()">Send</button>
                        <button @click="action_message()">Retake</button>
                        <button @click="removePopup()">Cancel</button>
                    </div>
                </div>
            `;
            
            this.showPopup(formContent);
        };
        reader.readAsDataURL(file);
    }
    
    
};
