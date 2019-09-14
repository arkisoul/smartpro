USE [ProjectSupervisions]
GO
/**** Object:  Table [dbo].[EngineerType]    Script Date: 11/8/2017 9:20:17 AM ****/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[EngineerType](
 [EngineerTypeId] [int] IDENTITY(1,1) NOT NULL,
 [EngineerType] [nvarchar](150) NULL,
 [Descriptions] [nvarchar](max) NULL,
 CONSTRAINT [PK_EngineerType] PRIMARY KEY CLUSTERED
(
 [EngineerTypeId] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]

GO
SET IDENTITY_INSERT [dbo].[EngineerType] ON

GO
INSERT [dbo].[EngineerType] ([EngineerTypeId], [EngineerType], [Descriptions]) VALUES (1, N' Architectural Engineering', N' Architectural Engineering')
GO
INSERT [dbo].[EngineerType] ([EngineerTypeId], [EngineerType], [Descriptions]) VALUES (2, N' Civil Engineering', N' Civil Engineering')
GO
INSERT [dbo].[EngineerType] ([EngineerTypeId], [EngineerType], [Descriptions]) VALUES (3, N'Structural Engineer', N'Structural Engineer')
GO
SET IDENTITY_INSERT [dbo].[EngineerType] OFF
GO
