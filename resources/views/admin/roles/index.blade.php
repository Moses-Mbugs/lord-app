<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Users & Roles | EcoBank Finance</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue: #0082BB;
            --dark-blue: #005B82;
            --green: #BED600;
            --dark-green: #669438;
            --gray: #464646;
            --light-gray: #EDEDED;
            --mid-gray: #979797;
            --bg: #EAF1F6;
            --danger: #C0392B;
            --success: #168A45;
            --border: #D7E4ED;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, var(--bg), #DDE8F0);
            color: var(--gray);
            min-height: 100vh;
        }

        .topbar {
            background: linear-gradient(135deg, var(--dark-blue), var(--blue));
            padding: 18px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 8px 24px rgba(0, 91, 130, 0.18);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand img {
            height: 28px;
            filter: brightness(0) invert(1);
        }

        .brand span {
            color: #fff;
            font-weight: 800;
            font-size: 15px;
            opacity: .9;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-pill {
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.32);
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            padding: 8px 16px;
            border-radius: 999px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background .2s;
        }

        .btn-pill:hover { background: rgba(255,255,255,0.26); color: #fff; }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px 40px 60px;
        }

        h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--dark-blue);
            margin-bottom: 24px;
        }

        .alert {
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13.5px;
            font-weight: 600;
        }

        .alert-success { background: #f0fff4; border: 1px solid #c6f6d5; color: var(--success); }
        .alert-error { background: #fff5f5; border: 1px solid #fed7d7; color: var(--danger); }

        .card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 12px 28px rgba(23, 50, 77, 0.07);
            padding: 26px;
            margin-bottom: 26px;
        }

        .card h2 {
            font-size: 16px;
            font-weight: 800;
            color: var(--dark-blue);
            margin-bottom: 16px;
        }

        .role-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }

        .role-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 7px 8px 7px 14px;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--dark-blue);
        }

        .role-chip form { display: inline; }

        .role-chip button {
            background: none;
            border: none;
            color: var(--mid-gray);
            font-size: 13px;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 50%;
        }

        .role-chip button:hover { color: var(--danger); }

        .protected-badge {
            font-size: 10px;
            text-transform: uppercase;
            color: var(--mid-gray);
            margin-left: 2px;
        }

        .new-role-form {
            display: flex;
            gap: 10px;
        }

        input[type="text"] {
            flex: 1;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 14px;
            font-family: inherit;
            font-size: 13.5px;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(0,130,187,0.12);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--blue), var(--dark-blue));
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            padding: 10px 20px;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid var(--border);
            font-size: 13.5px;
            vertical-align: top;
        }

        th {
            color: var(--dark-blue);
            font-weight: 800;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .user-roles-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 5px 12px 5px 8px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
        }

        .checkbox-label input { accent-color: var(--blue); }

        .btn-save {
            background: var(--dark-green);
            color: #fff;
            border: none;
            border-radius: 999px;
            font-weight: 700;
            font-size: 12px;
            padding: 6px 16px;
            cursor: pointer;
        }

        .muted { color: var(--mid-gray); font-size: 12.5px; }
    </style>
</head>
<body>

    <div class="topbar">
        <div class="brand">
            <img src="{{ asset('assets/img/Ecobank_Logo.png') }}" alt="EcoBank">
            <span>Users & Roles</span>
        </div>

        <div class="topbar-right">
            <a href="{{ route('home') }}" class="btn-pill">&larr; Back to Home</a>
        </div>
    </div>

    <div class="container">
        <h1>Users & Roles</h1>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <div class="card">
            <h2>Roles</h2>

            <div class="role-list">
                @foreach ($roles as $role)
                    <div class="role-chip">
                        {{ $role->name }}
                        @if (in_array($role->name, ['admin', 'finance-admin']))
                            <span class="protected-badge">protected</span>
                        @else
                            <form action="{{ route('admin.roles.destroy', $role) }}" method="POST"
                                onsubmit="return confirm('Delete the {{ $role->name }} role? This removes it from every user who has it.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Delete role">&times;</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>

            <form action="{{ route('admin.roles.store') }}" method="POST" class="new-role-form">
                @csrf
                <input type="text" name="name" placeholder="New role name, e.g. loans-admin" required>
                <button type="submit" class="btn-primary">Add Role</button>
            </form>
        </div>

        <div class="card">
            <h2>Assign Roles to Users</h2>

            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td class="muted">{{ $user->email }}</td>
                            <td colspan="2">
                                <form action="{{ route('admin.roles.sync', $user) }}" method="POST" class="user-roles-form">
                                    @csrf
                                    @foreach ($roles as $role)
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                                                @checked($user->roles->contains('id', $role->id))>
                                            {{ $role->name }}
                                        </label>
                                    @endforeach
                                    <button type="submit" class="btn-save">Save</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
