<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\DatePicker;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('User')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Toggle::make('is_admin')
                            ->label('Admin')
                            ->inline(false),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn($state) => $state ?: null) // keep old password if empty
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context) => $context === 'create')
                            ->minLength(8),
                    ])
                    ->columns(2),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                ToggleColumn::make('is_admin')->label('Admin')->sortable(),
                TextColumn::make('email_verified_at')->dateTime()->label('Verified at')->sortable(),
                TextColumn::make('created_at')->dateTime()->label('Created')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_admin')
                    ->label('Admin')
                    ->trueLabel('Admins')->falseLabel('Non-admins')
                    ->queries(
                        true: fn(Builder $q) => $q->where('is_admin', true),
                        false: fn(Builder $q) => $q->where('is_admin', false),
                        blank: fn(Builder $q) => $q
                    ),
                TernaryFilter::make('email_verified_at')
                    ->label('Email verified')
                    ->placeholder('All users')
                    ->trueLabel('Verified')->falseLabel('Unverified')
                    ->queries(
                        true: fn(Builder $q) => $q->whereNotNull('email_verified_at'),
                        false: fn(Builder $q) => $q->whereNull('email_verified_at'),
                        blank: fn(Builder $q) => $q
                    ),
                Tables\Filters\Filter::make('created_between')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        return $q
                            ->when($data['from'] ?? null, fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
