@extends('layouts.admin')

@section('title', '管理者用申請一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/history.css') }}">
@endsection

@section('content')
<h1 class="content-title">申請一覧</h1>

<div class="tabs">
    <label>
        <input type="radio" name="tab" checked>
        承認待ち
    </label>
    <div class="table__wrapper font-setting tab__inner">
        <table>
            <thead>
                <tr class="table__header">
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingRequests as $request)
                <tr class="table__inner">
                    <td>{{ $request->status_label }}</td>
                    <td>{{ $request->attendance->user->name }}</td>
                    <td>{{ $request->requested_check_in->format('Y/m/d') }}</td>
                    <td>{{ $request->reason }}</td>
                    <td>{{ $request->created_at->format('Y/m/d') }}</td>
                    <td class="detail__link">
                        <a href="#">詳細</a>
                    </td>
                </tr>
                @empty
                <tr class="table__inner">
                    <td colspan="6">申請はありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <label>
        <input type="radio" name="tab">
        承認済み
    </label>
    <div class="table__wrapper font-setting tab__inner">
        <table>
            <thead>
                <tr class="table__header">
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($approvedRequests as $request)
                <tr class="table__inner">
                    <td>{{ $request->status_label }}</td>
                    <td>{{ $request->attendance->user->name }}</td>
                    <td>{{ $request->requested_check_in->format('Y/m/d') }}</td>
                    <td>{{ $request->reason }}</td>
                    <td>{{ $request->created_at->format('Y/m/d') }}</td>
                    <td class="detail__link">
                        <a href="#">詳細</a>
                    </td>
                </tr>
                @empty
                <tr class="table__inner">
                    <td colspan="6">承認された申請はありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection