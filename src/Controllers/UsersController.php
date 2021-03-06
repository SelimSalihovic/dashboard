<?php

/**
 * @package     Dashboard
 * @version     1.0.0
 * @author      Ian Olson <ian@odotmedia.com>
 * @license     MIT
 * @copyright   2015, Odot Media LLC
 * @link        https://odotmedia.com
 */

namespace Odotmedia\Dashboard\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laracasts\Flash\Flash;
use Odotmedia\Dashboard\Exceptions\FormValidationException;
use Odotmedia\Dashboard\Exceptions\UsersException;
use Odotmedia\Dashboard\Services\Auth\AuthService;
use Odotmedia\Dashboard\Services\Role\RoleService;
use Odotmedia\Dashboard\Services\User\UserService;

class UsersController extends BaseDashboardController
{
    /**
     * Auth service instance.
     *
     * @var \Odotmedia\Dashboard\Services\Auth\AuthService
     */
    protected $authService;

    /**
     * User service instance.
     *
     * @var \Odotmedia\Dashboard\Services\User\UserService
     */
    protected $userService;

    /**
     * Role service instance.
     *
     * @var \Odotmedia\Dashboard\Services\Role\RoleService
     */
    protected $roleService;

    /**
     * The constructor.
     *
     * @param \Odotmedia\Dashboard\Services\Auth\AuthService $authService
     * @param \Odotmedia\Dashboard\Services\User\UserService $userService
     * @param \Odotmedia\Dashboard\Services\Role\RoleService $roleService
     */
    public function __construct(AuthService $authService, UserService $userService, RoleService $roleService)
    {
        $this->authService = $authService;
        $this->userService = $userService;
        $this->roleService = $roleService;

        parent::__construct($authService);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $users = $this->userService->getAllWith('roles');

        return $this->view('users.index')->with(['users' => $users]);
    }

    /**
     * Create a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $roles       = [];
        $roleChoices = $this->roleService->getAll();

        if (empty($roleChoices)) {
            return $this->view('users.create')->with('roles', $roles);
        }

        foreach ($roleChoices as $role) {
            $roles[$role->slug] = $role->name;
        }

        return $this->view('users.create')->with('roles', $roles);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            $this->userService->create($request->all());
        } catch (FormValidationException $e) {
            Flash::error($e->getMessage());

            return redirect()
              ->route('users.create')
              ->withErrors($e->getErrors())
              ->withInput();
        }

        Flash::success('User successfully created.');

        return redirect()->route('users.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        if (!$user = $this->userService->getByIdWith($id, 'roles')) {
            Flash::error('User does not exist.');

            return redirect()->route('users.index');
        }

        $currentRoles = ['Not Available'];
        $userRoles    = $user->getRoles();

        if (!empty($userRoles)) {
            $currentRoles = [];

            foreach ($userRoles as $userRole) {
                $currentRoles[] = $userRole->name;
            }
        }

        sort($currentRoles);

        $currentRoles = implode(', ', $currentRoles);

        $roles       = [];
        $roleChoices = $this->roleService->getAll();

        if (empty($roleChoices)) {
            return $this->view('users.create')->with(['user'        => $user,
                                                          'currentRoles' => $currentRoles,
                                                          'roles'       => $roles
            ]);
        }

        foreach ($roleChoices as $role) {
            $roles[$role->slug] = $role->name;
        }

        return $this->view('users.edit')->with(['user' => $user, 'currentRoles' => $currentRoles, 'roles' => $roles]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $this->userService->update($request->all(), $id);
        } catch (FormValidationException $e) {
            Flash::error($e->getMessage());

            return redirect()
              ->route('users.edit', ['id' => $id])
              ->withErrors($e->getErrors())
              ->withInput();
        } catch (UsersException $e) {
            Flash::error($e->getMessage());

            return redirect()->route('users.index');
        }

        Flash::success('User successfully updated.');

        return redirect()->route('users.edit', ['id' => $id]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete($id)
    {
        try {
            $this->userService->delete($id);
        } catch (UsersException $e) {
            Flash::error($e->getMessage());

            return redirect()->route('users.index');
        }

        Flash::success('User successfully deleted.');

        return redirect()->route('users.index');
    }
}