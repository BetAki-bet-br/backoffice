<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Navigation\FooterLinkSyncRequest;
use App\Http\Requests\Navigation\FooterRequest;
use App\Models\Domain\Navigation\Footer;
use App\Models\Domain\Navigation\FooterLink;
use App\Models\Domain\Navigation\FooterTranslation;
use App\Enums\ContentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *   name="Footers",
 *   description="Gerenciamento de rodapés (links, textos legais, disclaimers)"
 * )
 */
class FooterController extends Controller
{
    /**
     * Listar footers (cursor paginate)
     *
     * @OA\Get(
     *   path="/api/v1/footers",
     *   tags={"Footers"},
     *   security={{"bearerAuth": {}}},
     *   summary="Listar footers (cursor paginate)",
     *   @OA\Parameter(
     *     name="q",
     *     in="query",
     *     description="Busca pelo campo key",
     *     required=false,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="status",
     *     in="query",
     *     description="Filtrar por status (draft, published, archived)",
     *     required=false,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="country",
     *     in="query",
     *     description="Filtrar por país (BR, PT, ES, ...)",
     *     required=false,
     *     @OA\Schema(type="string", maxLength=2)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="current_page", type="integer", example=1),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="key", type="string", example="main-footer"),
     *           @OA\Property(property="status", type="string", example="draft"),
     *           @OA\Property(property="country", type="string", example="BR"),
     *           @OA\Property(property="brand", type="string", nullable=true, example="default")
     *         )
     *       ),
     *       @OA\Property(property="per_page", type="integer", example=15),
     *       @OA\Property(property="total", type="integer", example=1)
     *     )
     *   )
     * )
     */
    public function index(Request $request)
    {
        $query = Footer::query()
            ->with(['translations', 'links']);

        if ($search = $request->get('q')) {
            $query->where('key', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($country = $request->get('country')) {
            $query->where('country', $country);
        }

        return $query->orderBy('id', 'desc')->paginate();
    }

    /**
     * Criar footer
     *
     * @OA\Post(
     *   path="/api/v1/footers",
     *   tags={"Footers"},
     *   security={{"bearerAuth": {}}},
     *   summary="Criar novo footer",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"key"},
     *       @OA\Property(property="key", type="string", example="main-footer"),
     *       @OA\Property(property="status", type="string", example="draft"),
     *       @OA\Property(property="country", type="string", nullable=true, example="BR"),
     *       @OA\Property(property="brand", type="string", nullable=true, example="default"),
     *       @OA\Property(property="publish_at", type="string", format="date-time", nullable=true),
     *       @OA\Property(
     *         property="translations",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           required={"locale"},
     *           @OA\Property(property="locale", type="string", example="pt-BR"),
     *           @OA\Property(property="legal_title", type="string", nullable=true),
     *           @OA\Property(property="legal_text", type="string", nullable=true),
     *           @OA\Property(property="disclaimer", type="string", nullable=true)
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Footer criado",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="id", type="integer", example=1),
     *       @OA\Property(property="key", type="string", example="main-footer"),
     *       @OA\Property(property="status", type="string", example="draft"),
     *       @OA\Property(property="country", type="string", example="BR"),
     *       @OA\Property(property="brand", type="string", example="default"),
     *       @OA\Property(property="publish_at", type="string", format="date-time", nullable=true),
     *       @OA\Property(property="published_at", type="string", format="date-time", nullable=true),
     *       @OA\Property(
     *         property="translations",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=10),
     *           @OA\Property(property="locale", type="string", example="pt-BR"),
     *           @OA\Property(property="legal_title", type="string", nullable=true),
     *           @OA\Property(property="legal_text", type="string", nullable=true),
     *           @OA\Property(property="disclaimer", type="string", nullable=true)
     *         )
     *       ),
     *       @OA\Property(
     *         property="links",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=5),
     *           @OA\Property(property="block", type="string", example="legal"),
     *           @OA\Property(property="label", type="string", example="Política de Privacidade"),
     *           @OA\Property(property="url", type="string", example="/privacy-policy"),
     *           @OA\Property(property="target", type="string", example="_self"),
     *           @OA\Property(property="position", type="integer", example=0),
     *           @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function store(FooterRequest $request)
    {
        return DB::transaction(function () use ($request) {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $data = $request->validated();

            $footer = new Footer();
            $footer->fill($data);
            $footer->created_by = $user->id;
            $footer->updated_by = $user->id;
            $footer->save();

            if (!empty($data['translations'])) {
                $this->syncTranslations($footer, $data['translations']);
            }

            return $footer->fresh(['translations', 'links']);
        });
    }

    /**
     * Detalhar footer
     *
     * @OA\Get(
     *   path="/api/v1/footers/{footer}",
     *   tags={"Footers"},
     *   security={{"bearerAuth": {}}},
     *   summary="Obter detalhes de um footer",
     *   @OA\Parameter(
     *     name="footer",
     *     in="path",
     *     required=true,
     *     description="ID do footer",
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="id", type="integer", example=1),
     *       @OA\Property(property="key", type="string", example="main-footer"),
     *       @OA\Property(property="status", type="string", example="draft"),
     *       @OA\Property(property="country", type="string", example="BR"),
     *       @OA\Property(property="brand", type="string", example="default"),
     *       @OA\Property(property="publish_at", type="string", format="date-time", nullable=true),
     *       @OA\Property(property="published_at", type="string", format="date-time", nullable=true),
     *       @OA\Property(
     *         property="translations",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=10),
     *           @OA\Property(property="locale", type="string", example="pt-BR"),
     *           @OA\Property(property="legal_title", type="string", nullable=true),
     *           @OA\Property(property="legal_text", type="string", nullable=true),
     *           @OA\Property(property="disclaimer", type="string", nullable=true)
     *         )
     *       ),
     *       @OA\Property(
     *         property="links",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=5),
     *           @OA\Property(property="block", type="string", example="legal"),
     *           @OA\Property(property="label", type="string", example="Política de Privacidade"),
     *           @OA\Property(property="url", type="string", example="/privacy-policy"),
     *           @OA\Property(property="target", type="string", example="_self"),
     *           @OA\Property(property="position", type="integer", example=0),
     *           @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(response=404, description="Footer não encontrado")
     * )
     */
    public function show(Footer $footer)
    {
        return $footer->load(['translations', 'links']);
    }

    /**
     * Atualizar footer
     *
     * @OA\Put(
     *   path="/api/v1/footers/{footer}",
     *   tags={"Footers"},
     *   security={{"bearerAuth": {}}},
     *   summary="Atualizar footer existente",
     *   @OA\Parameter(
     *     name="footer",
     *     in="path",
     *     required=true,
     *     description="ID do footer",
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="key", type="string", example="main-footer"),
     *       @OA\Property(property="status", type="string", example="draft"),
     *       @OA\Property(property="country", type="string", nullable=true, example="BR"),
     *       @OA\Property(property="brand", type="string", nullable=true, example="default"),
     *       @OA\Property(property="publish_at", type="string", format="date-time", nullable=true),
     *       @OA\Property(
     *         property="translations",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           required={"locale"},
     *           @OA\Property(property="locale", type="string", example="pt-BR"),
     *           @OA\Property(property="legal_title", type="string", nullable=true),
     *           @OA\Property(property="legal_text", type="string", nullable=true),
     *           @OA\Property(property="disclaimer", type="string", nullable=true)
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Footer atualizado",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="id", type="integer", example=1),
     *       @OA\Property(property="key", type="string", example="main-footer"),
     *       @OA\Property(property="status", type="string", example="draft"),
     *       @OA\Property(property="country", type="string", example="BR"),
     *       @OA\Property(property="brand", type="string", example="default"),
     *       @OA\Property(property="publish_at", type="string", format="date-time", nullable=true),
     *       @OA\Property(property="published_at", type="string", format="date-time", nullable=true)
     *     )
     *   ),
     *   @OA\Response(response=404, description="Footer não encontrado"),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function update(FooterRequest $request, Footer $footer)
    {
        return DB::transaction(function () use ($request, $footer) {
            /** @var \App\Models\User $user */
            $user = $request->user();
            $data = $request->validated();

            $footer->fill($data);
            $footer->updated_by = $user->id;
            $footer->save();

            if (array_key_exists('translations', $data)) {
                $this->syncTranslations($footer, $data['translations'] ?? []);
            }

            return $footer->fresh(['translations', 'links']);
        });
    }

    /**
     * Remover footer
     *
     * @OA\Delete(
     *   path="/api/v1/footers/{footer}",
     *   tags={"Footers"},
     *   security={{"bearerAuth": {}}},
     *   summary="Remover footer",
     *   @OA\Parameter(
     *     name="footer",
     *     in="path",
     *     required=true,
     *     description="ID do footer",
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(
     *     response=204,
     *     description="Footer removido com sucesso"
     *   ),
     *   @OA\Response(response=404, description="Footer não encontrado")
     * )
     */
    public function destroy(Footer $footer)
    {
        $footer->delete();

        return response()->noContent();
    }

    /**
     * Publicar footer
     *
     * @OA\Post(
     *   path="/api/v1/footers/{footer}/publish",
     *   tags={"Footers"},
     *   security={{"bearerAuth": {}}},
     *   summary="Publicar footer",
     *   @OA\Parameter(
     *     name="footer",
     *     in="path",
     *     required=true,
     *     description="ID do footer",
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Footer publicado",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="id", type="integer", example=1),
     *       @OA\Property(property="key", type="string", example="main-footer"),
     *       @OA\Property(property="status", type="string", example="published"),
     *       @OA\Property(property="publish_at", type="string", format="date-time"),
     *       @OA\Property(property="published_at", type="string", format="date-time")
     *     )
     *   ),
     *   @OA\Response(response=404, description="Footer não encontrado")
     * )
     */
    public function publish(Request $request, Footer $footer)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $footer->status = ContentStatus::Published;
        $footer->publish_at = now();
        $footer->published_at = now();
        $footer->published_by = $user->id;
        $footer->save();

        return $footer->fresh(['translations', 'links']);
    }

    /**
     * Sincronizar links do footer
     *
     * @OA\Put(
     *   path="/api/v1/footers/{footer}/links",
     *   tags={"Footers"},
     *   security={{"bearerAuth": {}}},
     *   summary="Sincronizar links de um footer",
     *   @OA\Parameter(
     *     name="footer",
     *     in="path",
     *     required=true,
     *     description="ID do footer",
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"links"},
     *       @OA\Property(
     *         property="links",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", nullable=true, example=5),
     *           @OA\Property(property="block", type="string", nullable=true, example="legal"),
     *           @OA\Property(property="label", type="string", example="Política de Privacidade"),
     *           @OA\Property(property="url", type="string", example="/privacy-policy"),
     *           @OA\Property(property="icon", type="string", nullable=true, example="instagram"),
     *           @OA\Property(property="target", type="string", example="_self"),
     *           @OA\Property(property="position", type="integer", example=0),
     *           @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Links sincronizados",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="id", type="integer", example=1),
     *       @OA\Property(property="key", type="string", example="main-footer"),
     *       @OA\Property(property="status", type="string", example="draft")
     *     )
     *   ),
     *   @OA\Response(response=404, description="Footer não encontrado"),
     *   @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function syncLinks(FooterLinkSyncRequest $request, Footer $footer)
    {
        return DB::transaction(function () use ($request, $footer) {
            $linksData = $request->validated()['links'];

            $idsToKeep = [];

            foreach ($linksData as $index => $linkData) {
                $linkData['position'] = $linkData['position'] ?? $index;
                $linkData['target'] = $linkData['target'] ?? '_self';
                $linkData['is_active'] = $linkData['is_active'] ?? true;

                if (!empty($linkData['id'])) {
                    /** @var FooterLink $link */
                    $link = $footer->links()->whereKey($linkData['id'])->firstOrFail();
                    $link->fill($linkData);
                    $link->save();

                    $idsToKeep[] = $link->id;
                } else {
                    $link = $footer->links()->create($linkData);
                    $idsToKeep[] = $link->id;
                }
            }

            // Remove links que não estão mais na lista
            $footer->links()
                ->whereNotIn('id', $idsToKeep)
                ->delete();

            return $footer->fresh(['translations', 'links']);
        });
    }

    protected function syncTranslations(Footer $footer, array $translations): void
    {
        $idsToKeep = [];

        foreach ($translations as $translationData) {
            $translation = FooterTranslation::updateOrCreate(
                [
                    'footer_id' => $footer->id,
                    'locale'    => $translationData['locale'],
                ],
                [
                    'legal_title' => $translationData['legal_title'] ?? null,
                    'legal_text'  => $translationData['legal_text'] ?? null,
                    'disclaimer'  => $translationData['disclaimer'] ?? null,
                ]
            );

            $idsToKeep[] = $translation->id;
        }

        $footer->translations()
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }
}
