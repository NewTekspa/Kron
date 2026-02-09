<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Team;
use App\Models\Role;
use App\Models\TeamTaskIndicator;

class TeamController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $teams = Team::all();
        $teamIds = array_map(fn ($team) => (int) $team['id'], $teams);
        $membersByTeam = Team::membersForTeams($teamIds);
        $criticalByTeam = TeamTaskIndicator::countCriticalNotFinishedByTeam();
        $error = trim($_GET['error'] ?? '');

        $this->view('admin/teams/index', [
            'title' => 'Equipos',
            'teams' => $teams,
            'membersByTeam' => $membersByTeam,
            'criticalByTeam' => $criticalByTeam,
            'error' => $error !== '' ? $error : null,
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $nombre = trim($_POST['nombre'] ?? '');
        $subgerenteId = (int) ($_POST['subgerente_id'] ?? 0);
        $jefeId = (int) ($_POST['jefe_id'] ?? 0);

        if ($nombre === '' || $subgerenteId === 0 || $jefeId === 0 || $subgerenteId === $jefeId) {
            $this->redirect('/admin/equipos?error=Datos+incompletos');
        }

        $subgerenteRole = Role::getUserRoleName($subgerenteId);
        $jefeRole = Role::getUserRoleName($jefeId);

        if ($subgerenteRole !== 'subgerente' || $jefeRole !== 'jefe') {
            $this->redirect('/admin/equipos?error=Relacion+no+valida');
        }

        Team::create($nombre, $subgerenteId, $jefeId);

        $this->redirect('/admin/equipos');
    }

    public function assignMember(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $teamId = (int) ($_POST['team_id'] ?? 0);
        $colaboradorId = (int) ($_POST['colaborador_id'] ?? 0);

        if ($teamId === 0 || $colaboradorId === 0 || ! Team::exists($teamId)) {
            $this->redirect('/admin/equipos?error=Datos+incompletos');
        }

        $colaboradorRole = Role::getUserRoleName($colaboradorId);
        if ($colaboradorRole !== 'colaborador') {
            $this->redirect('/admin/equipos?error=Relacion+no+valida');
        }

        Team::assignMember($teamId, $colaboradorId);

        $this->redirect('/admin/equipos');
    }

    public function removeMember(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $teamId = (int) ($_POST['team_id'] ?? 0);
        $colaboradorId = (int) ($_POST['colaborador_id'] ?? 0);

        if ($teamId === 0 || $colaboradorId === 0) {
            $this->redirect('/admin/equipos?error=Datos+incompletos');
        }

        Team::removeMember($teamId, $colaboradorId);

        $this->redirect('/admin/equipos');
    }

    public function search(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $term = trim($_GET['q'] ?? '');
        $limit = (int) ($_GET['limit'] ?? 15);

        if (strlen($term) < 2) {
            header('Content-Type: application/json');
            echo json_encode([]);
            return;
        }

        $results = Team::searchByName($term, $limit);

        header('Content-Type: application/json');
        echo json_encode($results);
    }

    public function show(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(404);
            echo 'Equipo no encontrado.';
            return;
        }

        $team = Team::findWithLeaders($id);
        if (! $team) {
            http_response_code(404);
            echo 'Equipo no encontrado.';
            return;
        }

        $members = Team::members($id);

        $this->view('admin/teams/show', [
            'title' => 'Detalle de equipo',
            'team' => $team,
            'members' => $members,
        ]);
    }

    public function delete(): void
    {
        $this->requireLogin();
        $this->requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            Team::delete($id);
        }

        $this->redirect('/admin/equipos');
    }
}
