<?php
// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur as User;
use App\Models\Freelance;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Ajout pour le logging sécurisé
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Inscription avec envoi d'email de vérification
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            // Assurez-vous que 'utilisateurs' est le nom correct de votre table
            'email' => 'required|email|unique:utilisateurs,email',
            'telephone' => 'required|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
            'type_utilisateur' => 'required|in:client,freelance',
            'ville' => 'nullable|string|max:100',
            'adresse' => 'nullable|string',
            'titre_professionnel' => 'required_if:type_utilisateur,freelance|string|max:150',
            'categorie' => 'required_if:type_utilisateur,freelance|string|max:100',
            'biographie' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422); // 422 Unprocessable Entity pour les erreurs de validation
        }

        try {
            DB::beginTransaction();

            // Générer un token de vérification
            $verificationToken = Str::random(64);

            // Créer l'utilisateur
            $user = User::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'email' => $request->email,
                'telephone' => $request->telephone,
                'whatsapp' => $request->whatsapp ?? $request->telephone,
                'password' => Hash::make($request->password),
                'type_utilisateur' => $request->type_utilisateur,
                'ville' => $request->ville,
                'adresse' => $request->adresse,
                'statut' => 'actif',
                'verification_token' => $verificationToken,
            ]);

            // Créer le profil freelance si nécessaire
            if ($request->type_utilisateur === 'freelance') {
                Freelance::create([
                    'utilisateur_id' => $user->id,
                    'titre_professionnel' => $request->titre_professionnel,
                    'categorie' => $request->categorie,
                    'biographie' => $request->biographie,
                    'disponibilite' => 'disponible',
                ]);
            }

            // Envoyer l'email de vérification
            $frontendUrl = config('app.frontend_url');
            $verificationUrl = "{$frontendUrl}/verify-email?token={$verificationToken}&email={$user->email}";
            
            $user->notify(new VerifyEmailNotification($verificationUrl));

            DB::commit();

            // Créer le token d'authentification
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie ! Un email de vérification a été envoyé.',
                'data' => [
                    'user' => $user->load('freelance'),
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'email_verified' => false,
                ]
            ], 201); // 201 Created pour une ressource créée

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de l'inscription de l'utilisateur {$request->email}: " . $e->getMessage()); // 💡 Correction : log l'erreur
            
            //  Correction de Sécurité : Ne pas exposer l'erreur interne
            return response()->json([
                'success' => false,
                'message' => 'Une erreur interne est survenue lors de l\'inscription. Veuillez réessayer.',
            ], 500); // 500 Internal Server Error
        }
    }

    /**
     * Vérification de l'email
     */
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètres invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable.'
            ], 404);
        }

        // Vérifier si l'email est déjà vérifié
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Votre email est déjà vérifié. Vous pouvez vous connecter.',
                'data' => ['already_verified' => true]
            ], 200);
        }

        // Vérifier le token seulement si l'email n'est pas encore vérifié
        if ($user->verification_token !== $request->token) {
            return response()->json([
                'success' => false,
                'message' => 'Token de vérification invalide ou expiré.'
            ], 400);
        }

        // Marquer l'email comme vérifié
        $user->markEmailAsVerified();
        
        // Supprimer le token de vérification
        $user->update(['verification_token' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Email vérifié avec succès ! Vous pouvez maintenant vous connecter.',
            'data' => ['email_verified' => true]
        ], 200);
    }

    /**
     * Renvoyer l'email de vérification
     */
    public function resendVerificationEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable.'
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Votre email est déjà vérifié.'
            ], 400);
        }

        // Générer un nouveau token
        $verificationToken = Str::random(64);
        $user->update(['verification_token' => $verificationToken]);

        // Renvoyer l'email
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
        $verificationUrl = "{$frontendUrl}/verify-email?token={$verificationToken}&email={$user->email}";
        
        try {
            $user->notify(new VerifyEmailNotification($verificationUrl));
            
            return response()->json([
                'success' => true,
                'message' => 'Email de vérification renvoyé avec succès. Vérifiez votre boîte mail.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur envoi email de vérification pour ' . $user->email . ': ' . $e->getMessage()); // 💡 Correction : log l'erreur
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer plus tard.'
            ], 500);
        }
    }

    /**
     * Connexion
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        //  Correction : Utiliser un message générique pour les identifiants
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants (Email ou mot de passe) incorrects.'
            ], 401); // 401 Unauthorized
        }

        //  Précision : La vérification d'email est commentée. Je vous recommande de la réactiver.
        
        // if (!$user->hasVerifiedEmail()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Veuillez vérifier votre email avant de vous connecter.',
        //         'data' => [
        //             'email_verified' => false,
        //             'email' => $user->email
        //         ]
        //     ], 403);
        // }
        

        if ($user->statut !== 'actif') {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est ' . $user->statut
            ], 403); // 403 Forbidden
        }

        // Mettre à jour la dernière connexion
        $user->update(['derniere_connexion' => now()]);

        // Créer le token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => $user->load([
                    'freelance.competences',
                    'freelance.portofolios'
                ]),
                'token' => $token,
                'token_type' => 'Bearer',
                'email_verified' => $user->hasVerifiedEmail(), // Utiliser la vraie méthode
            ]
        ], 200);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ], 200);
    }

    /**
     * Utilisateur connecté (Me)
     *  Correction de Sécurité : Récupère l'utilisateur actuellement authentifié.
     */
    public function me(Request $request)
    {
        //  Correction : Utiliser $request->user() pour obtenir l'utilisateur connecté
        $user = $request->user()->load([
            'freelance.competences',
            'freelance.portofolios',
        ]);
        
        // Note: La route /user/profile/{id} devrait être changée en /user/profile
        // si vous utilisez $request->user() sans paramètre d'ID.
        
        return response()->json([
            'success' => true,
            'data' => $user,
        ], 200);
    }

    /**
     * Mise à jour du profil
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:100',
            'prenom' => 'sometimes|string|max:100',
            // L'email ne peut pas être changé facilement sans revérification, donc il est omis
            'telephone' => 'sometimes|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'ville' => 'nullable|string|max:100',
            'adresse' => 'nullable|string',
            // Sécurité : valider que l'image est bien une image
            'photo_profil' => 'nullable|image|max:2048', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['nom', 'prenom', 'telephone', 'whatsapp', 'ville', 'adresse']);

        if ($request->hasFile('photo_profil')) {
            // TODO: Ajouter la suppression de l'ancienne photo si elle existe
            $path = $request->file('photo_profil')->store('profils', 'public');
            $data['photo_profil'] = $path;
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'data' => $user->fresh()->load('freelance')
        ], 200);
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                //  Précision : 403 Forbidden est plus précis ici que 401 (qui est pour le manque de token)
                'message' => 'Mot de passe actuel incorrect.'
            ], 403); 
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe modifié avec succès'
        ], 200);
    }

    /**
     * ADMIN : Lister tous les utilisateurs (nécessite rôle Admin)
     */
    public function getUsers(Request $request)
    {
        //  Correction de Sécurité : Vérification de l'autorisation
        if ($request->user()->type_utilisateur !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.'
            ], 403); 
        }

        $query = User::with('freelance');
        
        if ($request->has('type')) {
            $query->where('type_utilisateur', $request->type);
        }
        
        if ($request->has('status')) {
            $query->where('statut', $request->status);
        }
        
        $users = $query->paginate($request->per_page ?? 15);
        
        return response()->json([
            'success' => true,
            'data' => $users
        ], 200);
    }

    /**
     * ADMIN : Mettre à jour le statut d'un utilisateur (nécessite rôle Admin)
     */
    public function updateUserStatus(Request $request, $id)
    {
        //  Correction de Sécurité : Vérification de l'autorisation
        if ($request->user()->type_utilisateur !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.'
            ], 403); 
        }

        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:actif,suspendu,banni', // Ajout d'une validation pour le statut
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::findOrFail($id);
        $user->statut = $request->statut;
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour',
            'data' => $user
        ], 200);
    }
}
