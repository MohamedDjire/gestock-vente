// Fonction utilitaire simple pour la modale d’écriture : upload et retourne juste l’URL
export async function uploadToCloudinary(file) {
  const res = await uploadPhoto(file)
  if (res.success && res.data && res.data.url) return res.data.url
  throw new Error(res.message || 'Erreur upload Cloudinary')
}

import axios from 'axios'

export const cloudinaryConfig = {
  cloudName: 'dwyibyy50', // Ton cloud name
  uploadPreset: 'mariam', // Ton upload preset
  apiKey: '482413326578641', // (optionnel côté front, utile si tu veux l'ajouter)
  uploadUrl: 'https://api.cloudinary.com/v1_1/dwyibyy50/image/upload', // Remplace <cloud_name> si tu veux fixer l'URL
}

/**
 * Upload une photo sur Cloudinary avec validation, nommage, gestion d'erreur et suivi de progression
 * @param {File} file - Fichier image à uploader
 * @param {string|null} membreId - (optionnel) ID du membre pour le nommage
 * @param {string} altText - (optionnel) Texte alternatif
 * @returns {Promise<{success: boolean, data?: object, message: string}>}
 */
export const uploadPhoto = async (file, membreId = null, altText = "") => {
  try {
    console.log("🔄 Upload de la photo vers Cloudinary...")
    console.log("📤 Fichier:", file?.name, file?.size, "bytes")

    // Validation du fichier
    if (!file) {
      throw new Error("Aucun fichier fourni")
    }

    // Vérifier le type de fichier
    const allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp"]
    if (!allowedTypes.includes(file.type)) {
      throw new Error("Type de fichier non supporté. Utilisez JPG, PNG, GIF ou WebP.")
    }

    // Vérifier la taille (max 5MB)
    const maxSize = 5 * 1024 * 1024 // 5MB
    if (file.size > maxSize) {
      throw new Error("Le fichier est trop volumineux. Taille maximum: 5MB")
    }

    // Créer un nom de fichier unique
    const timestamp = Date.now()
    const fileName = membreId
      ? `membre_${membreId}_${timestamp}_${file.name.replace(/\s+/g, "_")}`
      : `photo_${timestamp}_${file.name.replace(/\s+/g, "_")}`

    // Préparer les données pour Cloudinary
    const formData = new FormData()
    formData.append("file", file)
    formData.append("upload_preset", cloudinaryConfig.uploadPreset)
    // Ne pas envoyer api_key côté front pour un upload unsigned
      // Après upload, tu peux envoyer l'URL à ton backend pour la sauvegarder dans la table stock_utilisateur
      // Exemple d'appel API (à adapter selon ton backend) :
      // if (response.data && response.data.secure_url) {
      //   await axios.post('/api/utilisateur/update-photo', {
      //     userId: membreId, // ou l'id de l'utilisateur courant
      //     photo: response.data.secure_url
      //   })
      // }
    formData.append("public_id", fileName)
    formData.append("folder", "prostock") // Personnalise le dossier si besoin
    if (altText) formData.append("context", `alt=${altText}`)

    // Déterminer l'URL d'upload
    const uploadUrl = cloudinaryConfig.uploadUrl.replace('dwyibyy50', cloudinaryConfig.cloudName)

    console.log("📤 Upload vers Cloudinary... URL:", uploadUrl)

    // Upload vers Cloudinary avec suivi de progression
    const response = await axios.post(uploadUrl, formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
      timeout: 30000, // 30 secondes
      onUploadProgress: (progressEvent) => {
        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total)
        console.log(`📤 Progression upload: ${percentCompleted}%`)
      },
    })

    if (response.data && response.data.secure_url) {
      console.log("✅ Photo uploadée avec succès:", response.data.secure_url)
      return {
        success: true,
        data: {
          url: response.data.secure_url,
          public_id: response.data.public_id,
          width: response.data.width,
          height: response.data.height,
          format: response.data.format,
          bytes: response.data.bytes,
          created_at: response.data.created_at,
        },
        message: "Photo uploadée avec succès",
      }
    } else {
      throw new Error("Réponse Cloudinary invalide")
    }

  } catch (error) {
    console.error("❌ Erreur lors de l'upload de la photo:", error)
    if (error.response) {
      // Erreur de Cloudinary
      const cloudinaryError = error.response.data?.error?.message || error.response.statusText
      throw new Error(`Erreur Cloudinary: ${cloudinaryError}`)
    } else if (error.code === "ECONNABORTED") {
      throw new Error("Timeout lors de l'upload. Le fichier est peut-être trop volumineux.")
    } else {
      throw new Error(`Erreur lors de l'upload: ${error.message}`)
    }
  }
}
